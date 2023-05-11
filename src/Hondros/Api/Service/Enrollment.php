<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Api\Client;
use Hondros\Api\Util\Helper\QuestionUtil;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Laminas\Config\Config;

class Enrollment extends ServiceAbstract
{
    use QuestionUtil { filterActiveOnly as protected; }

    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Enrollment';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Enrollment';
    
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
     * @var \Hondros\Api\Model\Repository\Exam
     */
    protected $examRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\User
     */
    protected $userRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Organization
     */
    protected $organizationRepository;

    /**
     * @var \Hondros\Api\Client\MailChimp
     */
    protected $mailChimpClient;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Enrollment $repository, Config $config, 
        Repository\Exam $examRepository, Repository\Module $moduleRepository, Repository\Question $questionRepository,
        Repository\User $userRepository, Repository\Organization $organizationRepository, Client\MailChimp $mailChimpClient)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->config = $config;
        $this->examRepository = $examRepository;
        $this->moduleRepository = $moduleRepository;
        $this->questionRepository = $questionRepository;
        $this->userRepository = $userRepository;
        $this->organizationRepository = $organizationRepository;
        $this->mailChimpClient = $mailChimpClient;
    }

    /**
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\ORMException
     */
    public function save($params)
    {   
        // create new
        if (empty($params['id'])) {
           return $this->createNew($params);
        }
    }

    /**
     * @param int $id
     * @return DoctrineSingle
     */
    public function enable($id)
    {
        return $this->update($id, ['status' => 1]);
    }

    /**
     * @param int $id
     * @return DoctrineSingle
     */
    public function disable($id)
    {
        return $this->update($id, ['status' => 0]);
    }

    /**
     * Extend the expiration date for an enrollment. If the enrollment is
     * already expired, it'll add today plus the passed in amount. Else
     * it'll add the number of time to the existing expiration date.
     *
     * @param int $id
     * @param array $params
     * @return DoctrineSingle
     * @throws InvalidArgumentException
     */
    public function extend(int $id, array $params): DoctrineSingle
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        /** @var Entity\Enrollment $enrollment */
        $enrollment = $this->repository->find($id);

        // Find out what timeframe we want to extend to
        if (empty($params['days']) || filter_var($params['days'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid timeframe.", 400);
        }

        $days = $params['days'];

        $today = new \DateTime();
        $expirationDate = $enrollment->getExpiration();
        $newExpirationDate = null;

        if ($expirationDate > $today) {
            $newExpirationDate = clone $expirationDate->modify("+{$days} days");
        } else {
            $newExpirationDate = clone $today->modify("+{$days} days");;
        }

        $enrollment->setExpiration($newExpirationDate);
        $enrollment->setModified(new \DateTime());
        $this->entityManager->flush();

        return new DoctrineSingle($enrollment, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $id
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }
        
        // for now until we find a better way, remove objects
        unset($params['user']);
        unset($params['exam']);
        unset($params['organization']);
        unset($params['progresses']);

        // get module and see what it's current stats are
        /** @var Entity\Enrollment $enrollment */
        $enrollment = $this->repository->find($id);

        if (empty($enrollment)) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        // might need to track somethings here - auditing?
        $type = !empty($params['type']) ? trim($params['type']) : null;
        if (!empty($type)) {
            if (!$enrollment->isValidType($type)) {
                throw new InvalidArgumentException("Invalid enrollment type.", 400);
            }
        }

        // if trial enrollment moving to paid
        if ($enrollment->getType() == $enrollment::TYPE_TRIAL && $type == $enrollment::TYPE_TRIAL_CONVERTED) {
            $user = $enrollment->getUser();

            $subscriber = (new Client\MailChimp\Subscriber())
                ->setEmail($user->getEmail())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setEnrollmentDate($enrollment->getCreated())
                ->setProductId($enrollment->getExternalOrderId());

            $this->mailChimpClient->addSubscriberToList($this->config->mailChimp->list->converted, $subscriber);

            // now remove from old list
            $this->mailChimpClient->removeFromList($this->config->mailChimp->list->trial, $user->getEmail());

            // update converted
            $enrollment->setConverted(new \DateTime());
        }

        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Enrollment();
        $hydrator = $strategy->getHydrator();
        $enrollment = $hydrator->hydrate($params, $enrollment);
        $enrollment->setModified(new \DateTime());

        $this->entityManager->flush();

        return new DoctrineSingle($enrollment, self::ENTITY_STRATEGY);
    }

    /**
     * @param $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createNew($params)
    {
        $date = new \DateTime();
        
        // perpetual enrollment?
        $perpetual = false;
        if (isset($params['perpetual'])) {
            $perpetual = filter_var($params['perpetual'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($params['perpetual'] === '' || $perpetual === null) {
                throw new InvalidArgumentException("Perpetual parameter must be of boolean value.", 400);
            }
        }

        // validate user id
        if (empty($params['userId']) || false === $userId = filter_var(trim($params['userId']), FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("Invalid user id.", 400);
        }

        // get user
        $user = $this->userRepository->find($userId);
        
        if (empty($user)) {
            throw new InvalidArgumentException("User not found.", 400);
        }
        
        // get exam by id or code
        if (!empty($params['examId'])) {
            $exam = $this->examRepository->find(trim($params['examId']));
        } elseif (!empty($params['examCode'])) {
            $exam = $this->examRepository->findOneBy(['code' => trim($params['examCode'])]);
        } else {
            throw new InvalidArgumentException("Invalid exam id.", 400);
        }
        
        if (empty($exam)) {
            throw new InvalidArgumentException("Invalid exam id.", 400);
        }

        // validate organization id
        if (empty($params['organizationId']) || false === $organizationId = filter_var(trim($params['organizationId']), FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("Invalid organization id.", 400);
        }

        /** @var Entity\Organization $organization */
        $organization = $this->organizationRepository->find($organizationId);
        
        if (empty($organization)) {
            throw new InvalidArgumentException("Invalid organization id.", 400);
        }

        // before moving forward, make sure we don't already have an enrollment for this user/exam
        $enrollment = $this->repository->findOneBy([
            'user' => $user->getId(),
            'exam' => $exam->getId()
        ]); 

        // in the future this might be where we need to update expiration for extensions
        if (!empty($enrollment)) {
            throw new InvalidArgumentException("User {$user->getId()} already has an enrollment with exam {$exam->getId()}.", 400);
        }
        
        // figure out expiration
        $expiration = null;
        
        // does the exam have an a set access time?
        if (!$perpetual && null !== $accessTime = $exam->getAccessTime()) {
            $expiration = $date->add(new \DateInterval("P{$accessTime}D"));
        }

        // if here, we are good to create a new enrollment
        $enrollment = (new Entity\Enrollment())
            ->setUser($user)
            ->setUserId($user->getId())
            ->setExam($exam)
            ->setExamId($exam->getId())
            ->setOrganization($organization)
            ->setOrganizationId($organization->getId())
            ->setStatus(Entity\Enrollment::STATUS_ACTIVE)
            ->setShowPreAssessment(true)
            ->setCreated(new \DateTime())
            ->setExpiration($expiration);

        // valid enrollment type?
        $type = Entity\Enrollment::TYPE_FULL;
        if (!empty($params['type'])) {
            $type = $params['type'];
            if (!$enrollment->isValidType($type)) {
                throw new InvalidArgumentException("Invalid enrollment type.", 400);
            }
        }

        $enrollment->setType($type);

        // only set conversion for full paid
        if ($type == $enrollment::TYPE_FULL) {
            $enrollment->setConverted($date);
        }

        // external id passed?
        if (!empty($params['externalOrderId'])) {
            $enrollment->setExternalOrderId($params['externalOrderId']);
        }
        
        $this->entityManager->persist($enrollment);
        $this->entityManager->flush($enrollment);

        /** @var Entity\Module[] $modules */
        $modules = $this->moduleRepository->findForExam($enrollment->getExamId());

        // create progress
        foreach ($modules as $module) {
            try {
                $this->createProgressWithQuestions($enrollment, $module, Entity\ModuleQuestion::TYPE_STUDY);
            } catch (\Exception $e) {
                $this->logger->error("Unable to create enrollment study progress for {$enrollment->getId()} "
                    . "and module {$module->getId()} due to {$e->getMessage()}");
            }

            try {
                $this->createProgressWithQuestions($enrollment, $module, Entity\ModuleQuestion::TYPE_PRACTICE);
            } catch (\Exception $e) {
                $this->logger->error("Unable to create enrollment practice progress for {$enrollment->getId()} "
                    . "and module {$module->getId()} due to {$e->getMessage()}");
            }
        }

        // save the rest
        $this->entityManager->flush();

        // add new enrollment trial enrollments to mail chimp list
        if ($enrollment->getType() == $enrollment::TYPE_TRIAL) {
            $subscriber = (new Client\MailChimp\Subscriber())
                ->setEmail($user->getEmail())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setProductId($enrollment->getExternalOrderId());

            $this->mailChimpClient->addSubscriberToList($this->config->mailChimp->list->trial, $subscriber);
        }

        // where they in the pending list? if so, remove and add to enrolled
        if (strtolower($organization->getName()) == strtolower(Entity\Organization::COMPUCRAM)
            && $this->mailChimpClient->isSubscriberInList($this->config->mailChimp->list->pendingPayment, $user->getEmail())) {
            // remove from the pending payment list
            $this->mailChimpClient->removeFromList($this->config->mailChimp->list->pendingPayment, $user->getEmail());

            // we want to add all enrollments for compucram to mailchimp list
            $subscriber = (new Client\MailChimp\Subscriber())
                ->setEmail($user->getEmail())
                ->setFirstName($user->getFirstName())
                ->setLastName($user->getLastName())
                ->setProductCode($exam->getCode())
                ->setIndustryName($exam->getIndustry()->getName());

            $this->mailChimpClient->addSubscriberToList($this->config->mailChimp->list->enrolled, $subscriber);
        }

        // return the module attempt info, with questions and answers
        return new DoctrineSingle($enrollment, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $enrollmentId
     * @param int $moduleId
     * @param array $params
     * @return Entity\Progress
     * @throws \Doctrine\ORM\ORMException
     */
    public function createModuleProgress($enrollmentId, $moduleId, array $params)
    {
        $type = !empty($params['type']) ? $params['type'] : null;

        if (empty($enrollmentId)) {
            throw new InvalidArgumentException("Invalid enrollment id.", 400);
        }

        if (empty($moduleId)) {
            throw new InvalidArgumentException("Invalid module id.", 400);
        }

        if (!in_array($type, [Entity\Progress::TYPE_STUDY, Entity\Progress::TYPE_PRACTICE])) {
            throw new InvalidArgumentException("Invalid type specified.", 400);
        }

        $enrollment = $this->repository->find($enrollmentId);

        if (empty($enrollment)) {
            throw new InvalidArgumentException("Enrollment not found.", 400);
        }

        $module = $this->moduleRepository->find($moduleId);

        if (empty($module)) {
            throw new InvalidArgumentException("Module not found.", 400);
        }

        // create all progress with questions
        $progress = $this->createProgressWithQuestions($enrollment, $module, $type);

        // save
        $this->entityManager->flush();

        return new DoctrineSingle($progress, Progress::ENTITY_STRATEGY);
    }

    /**
     * We want to take some actions on pending enrollments such as add the user to a MailChimp list
     *
     * @param array $params
     * @return mixed
     */
    public function savePending($params)
    {
        if (empty($params['email']) || false === $email = filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email.", 400);
        }

        if (empty($params['firstName']) || false === $firstName = filter_var($params['firstName'],
                FILTER_SANITIZE_STRING)) {
            throw new InvalidArgumentException("Invalid first name.", 400);
        }

        if (empty($params['lastName']) || false === $lastName = filter_var($params['lastName'],
                FILTER_SANITIZE_STRING)) {
            throw new InvalidArgumentException("Invalid last name.", 400);
        }

        if (empty($params['productIds']) || false === $productIds = filter_var($params['productIds'],
                FILTER_SANITIZE_STRING)) {
            throw new InvalidArgumentException("Invalid product ids.", 400);
        }

        $subscriber = (new Client\MailChimp\Subscriber())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setProductId($productIds);

        $subscriber = $this->mailChimpClient->addSubscriberToList($this->config->mailChimp->list->pendingPayment, $subscriber);

        return [
            'id' => ($subscriber ? $subscriber->getId() : false)
        ];
    }

    /**
     * @param Entity\Enrollment $enrollment
     * @param Entity\Module $module
     * @param string $type
     * @return Entity\Progress
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createProgressWithQuestions(Entity\Enrollment $enrollment, Entity\Module $module, $type)
    {
        // get all questions
        $questions = $this->questionRepository->findForModule($module->getId(), $type);
        $questions = $this->filterActiveOnly($questions);

        // create progress
        $progress = (new Entity\Progress())
            ->setEnrollment($enrollment)
            ->setEnrollmentId($enrollment->getId())
            ->setModule($module)
            ->setModuleId($module->getId())
            ->setType($type)
            ->setQuestionCount(count($questions))
            ->setCreated(new \DateTime())
            ->setModified(new \DateTime());

        $this->entityManager->persist($progress);

        // add question progresses
        foreach ($questions as $question) {
            $progressQuestion = (new Entity\ProgressQuestion())
                ->setQuestion($this->entityManager->getReference('Hondros\Api\Model\Entity\Question', $question->getId()))
                ->setProgress($progress);

            $this->entityManager->persist($progressQuestion);
        }

        unset($questions);

        return $progress;
    }
}
