<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use Laminas\Config\Config;
use InvalidArgumentException;
use DomainException;
use DateTime;
use Predis\Client as Redis;

class User extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\User';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'User';
    
    const PASSWORD_RESET_CACHE_PREFIX = 'passwordreset:';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Enrollment
     */
    protected $repository;
    
    /**
     * @var \Laminas\Config\Config
     */
    protected $config;
    
    /**
     * @var \Predis\Client;
     */
    protected $redis;

    /**
     * @var \Hondros\Api\Model\Entity\User
     */
    protected $authUser;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\User $repository, Config $config,
        Redis $redis, Entity\User $authUser)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->config = $config;
        $this->redis = $redis;
        $this->authUser = $authUser;
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return DoctrineSingle|null|object
     */
    public function login($email, $password, $remember = false)
    {
        
        $user = $this->repository->findOneBy(['email' => $email]);
        
        if (empty($user)) {
            throw new InvalidArgumentException("Invalid Login.", 401);
        }
        
        // validate password - password should be hash:md5(salt.password)
        $hashArray = explode(':', $user->getPassword());
        if (empty($hashArray) || count($hashArray) !== 2 || md5($hashArray[1] . $password) !== $hashArray[0]) {
            throw new InvalidArgumentException("Invalid Login.", 401);
        }
        
        // enabled/disabled?
        if (!$user->getStatus()) {
            throw new DomainException("User account is disabled.", 403);
        }
        
        // update last login
        $user->setLastLogin(new DateTime());
        $this->entityManager->flush();
        
        $user = new DoctrineSingle($user, self::ENTITY_STRATEGY);
        
        // store in cookie for app can use
        if ((boolean) $remember) {
            $this->createLoginCookie($user);
        }
        
        return $user;
    }
    
    /**
     * @todo combine with login
     * @param string $email
     * @param string $token
     * @param bool $remember added for testing, should always be true
     * @throws InvalidArgumentException
     * @return \Hondros\Common\DoctrineSingle
     */
    public function loginSso($email, $token, $remember = true)
    {
        //$user = $this->repository->findOneBy(['email' => $email, 'token' => $token]);
        
        // quick fix for issue with users changing email addresses in Hondros system
        $user = $this->repository->findOneBy(['token' => $token]);
    
        if (empty($user)) {
            throw new InvalidArgumentException("Invalid Login.", 401);
        }
        
        // enabled/disabled?
        if (!$user->getStatus()) {
            throw new DomainException("User account is disabled.", 403);
        }
    
        // update last login
        $user->setLastLogin(new DateTime());
        $this->entityManager->flush();
    
        $user = new DoctrineSingle($user, self::ENTITY_STRATEGY);
        
        // always create the login cookie for sso
        if ($remember) {
            $this->createLoginCookie($user);
        }

        return $user;
    }
    
    /**
     * Change this to use redis so we can track user id and remove user id when
     * we disable a users. Currently, if we disable a user and they have a cookie,
     * we can't delete their cookie to load the new user data
     * @todo change to use redis
     */
    public function logout()
    {
        // remove cookie
        if (isset($_COOKIE['user'])) {
            unset($_COOKIE['user']);
            setcookie('user', null, -1, '/');
        }
    }
    
    /**
     * Takes in an email and creates temporary password reset code
     * that is emailed to the user.
     * 
     * @param string $email
     * @return array
     * @throws InvalidArgumentException
     */
    public function requestPasswordReset($email)
    {
        // validate email
        if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid id email address.", 400);
        }
        
        $user = $this->repository->findOneBy(['email' => $email]);
        
        if (empty($user)) {
            throw new InvalidArgumentException("User not found.", 409);
        }
        
        // if found, create temp token and send out email
        $code = md5(uniqid() . $email);
        $cacheKey = self::PASSWORD_RESET_CACHE_PREFIX . $code;
        
        $this->redis->set($cacheKey, $email);
        $this->redis->expire($cacheKey, $this->config->email->passwordReset->codeExpiration);
        
        // load template, update
        $resetUrl = $this->config->examPrepApp->resetUrl . "?code={$code}&email=" . urlencode($email);
        $file = realpath(getcwd() . $this->config->email->passwordReset->templatePath);
        $emailTemplate = file_get_contents($file);

        $emailTemplate = str_replace('{{USER_NAME}}', $user->getFirstName() . ' ' . $user->getLastName(), $emailTemplate);
        $emailTemplate = str_replace('{{SITE_URL}}', $this->config->examPrepApp->baseUrl, $emailTemplate);
        $emailTemplate = str_replace('{{SITE_LOGO_URL}}', $this->config->examPrepApp->logoUrl, $emailTemplate);
        $emailTemplate = str_replace('{{RESET_URL}}', $resetUrl, $emailTemplate);
        
        // send
        $from = $this->config->email->passwordReset->sender;
        $subject = $this->config->email->passwordReset->subject;
        
        $headers = "From: " . strip_tags($from) . "\n";
        $headers .= "Reply-To: ". strip_tags($from) . "\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\n";
        
        // send mail
        mail($email, $subject, $emailTemplate, $headers);
        
        return [
            'code' => $code
        ];
    }
    
    /**
     * Verify the email and code match, and update the users password
     * 
     * @param string $email
     * @param string $password
     * @param string $code
     * @return array
     */
    public function updatePassword($email, $password, $code)
    {
        // validate email
        if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address.", 400);
        }

        // @todo need to specify some password restricts and put in the user entity
        if (empty($password) || strlen($password) < 5) {
            throw new InvalidArgumentException("Invalid password.", 400);
        }
        
        // check the code in redis
        $cacheKey = self::PASSWORD_RESET_CACHE_PREFIX . $code;
        $cacheEmail = $this->redis->get($cacheKey);
        if (empty($cacheEmail)) {
            throw new \InvalidArgumentException("Invalid code.", 400);
        }
        
        // is the code for the right email account?
        if (strtolower($cacheEmail) != strtolower($email)) {
            throw new InvalidArgumentException("Invalid code.", 400);
        }
        
        // is there a user with that email?
        $user = $this->repository->findOneBy(['email' => $email]);
        
        if (empty($user)) {
            throw new InvalidArgumentException("User not found.", 400);
        }
        
        // if we are here, we are good to go
        $user->setPassword($password);
        $user->setModified(new \DateTime());
        
        // save
        $this->entityManager->flush();
        
        // remove key
        $this->redis->del($cacheKey);
        
        // done
        return new DoctrineSingle($user, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $id
     * @return array
     */
    public function resetToken($id)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        /** @var Entity\User $user */
        $user = $this->repository->find($id);

        if (empty($user)) {
            throw new InvalidArgumentException("User not found.", 409);
        }

        $user->setToken($user->generateToken());
        $user->setModified(new \DateTime());

        $this->entityManager->flush();

        return [
            'token' => $user->getToken()
        ];
    }

    /**
     * @param string $email
     * @param array $params
     * @return DoctrineSingle
     */
    public function findByEmail($email, $params = [])
    {
        // validate email
        if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address.", 400);
        }
        
        $user = $this->repository->findOneBy(['email' => $email]);
        
        if (empty($user)) {
            throw new InvalidArgumentException("User not found.", 404);
        }
        
        return new DoctrineSingle($user, self::ENTITY_STRATEGY);
    }

    /**
     * @param array $params
     * @return DoctrineSingle
     */
    public function save($params)
    {
        // create new
        if (empty($params['id'])) {
            return $this->createNew($params);
        }
    }

    /**
     * @param int $userId
     * @return DoctrineSingle
     */
    public function enable($userId)
    {
        return $this->update($userId, ['status' => Entity\User::STATUS_ACTIVE]);
    }

    /**
     * @param int $userId
     * @return DoctrineSingle
     */
    public function disable($userId)
    {
        return $this->update($userId, ['status' => Entity\User::STATUS_INACTIVE]);
    }

    /**
     * @param int $id
     * @param array $params
     * @return DoctrineSingle
     */
    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        /** @var Entity\User $user */
        $user = $this->repository->find($id);

        if (empty($user)) {
            throw new \InvalidArgumentException("User not found.", 404);
        }

        // only admin can set roles
        if ($this->authUser->getRole() !== Entity\User::ROLE_ADMIN && isset($params['role'])) {
            unset($params['role']);
        }

        // don't allow updating token
        if (isset($params['token'])) {
            unset($params['token']);
        }

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\User())
            ->getHydrator();
        $hydrator->hydrate($params, $user);
        $user->setModified(new \DateTime());

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($user, self::ENTITY_STRATEGY);
    }

    /**
     * @param array $params
     * @return DoctrineSingle
     */
    protected function createNew($params)
    {
        $date = new DateTime();
    
        // validate
        if (empty($params['email']) || filter_var($params['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("Invalid email address.", 400);
        }
        
        if (empty($params['password']) && empty($params['passwordHash'])) {
            throw new InvalidArgumentException("Invalid password.", 400);
        }
        
        if (empty($params['firstName'])) {
            throw new InvalidArgumentException("Invalid first name.", 400);
        }
        
        if (empty($params['lastName'])) {
            throw new InvalidArgumentException("Invalid last name.", 400);
        }
        
        // is user already in the system?
        $users = $this->repository->findByEmail($params['email']);
        
        if (!empty($users)) {
            throw new InvalidArgumentException("A user by that email address already exists.", 400);
        }

        /** @var Entity\User $user */
        $user = new Entity\User();
        $user->setEmail($params['email'])
            ->setToken($user->generateToken())
            ->setFirstName($params['firstName'])
            ->setLastName($params['lastName'])
            ->setCreated($date)
            ->setModified($date)
            ->setStatus(1);

        // only admin can set roles
        if ($this->authUser->getRole() === Entity\User::ROLE_ADMIN && isset($params['role'])) {
            $user->setRole($params['role']);
        }
        
        // do we have a password or password hash?
        if (!empty($params['passwordHash'])) {
            $user->setPassword($params['passwordHash'], false);
        } else {
            $user->setPassword($params['password']);
        }
    
        $this->entityManager->persist($user);
       
        // save
        $this->entityManager->flush();
    
        // return the module attempt info, with questions and answers
        return new DoctrineSingle($user, self::ENTITY_STRATEGY);
    }
    
    protected function createLoginCookie($user)
    {
        setcookie("user", json_encode($user), time() + (int) $this->config->loginCookie, '/', '', true);
    }
}
