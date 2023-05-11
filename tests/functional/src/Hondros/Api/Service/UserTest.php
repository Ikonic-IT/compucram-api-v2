<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Api\Service;
use Hondros\Test\FunctionalAbstract;

class UserTest extends FunctionalAbstract
{
    use EntityGeneratorUtil {}

    /**
     * @var \Hondros\Api\Service\User
     */
    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->getServiceManager()->get('userService');
    }

    protected function tearDown(): void
    {
        $this->userService = null;

        parent::tearDown();
    }

    /**
     * @param string $email
     * @param string $errorMessage
     * @dataProvider dataProviderFindByEmail
     */
    public function testFindByEmail($email, $errorMessage)
    {
        try {
            $response = $this->userService->findByEmail($email);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($email, $response['email']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneByEmail($email);
        $this->assertNotEmpty($user);

        $this->assertEquals($user->getFirstName(), $response['firstName']);
        $this->assertEquals($user->getLastName(), $response['lastName']);
        $this->assertEquals($user->getLastName(), $response['lastName']);
        $this->assertEquals($user->getToken(), $response['token']);
        $this->assertEquals($user->getRole(), $response['role']);
        $this->assertEquals($user->getStatus(), $response['status']);

        // make sure the password doesn't show
        $this->assertArrayNotHasKey('password', $response);
    }

    /**
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderCreate
     */
    public function testCreate($params = [], $errorMessage)
    {
        try {
            $response = $this->userService->save($params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($params['email'], $response['email']);
        $this->assertEquals($params['firstName'], $response['firstName']);
        $this->assertEquals($params['lastName'], $response['lastName']);
        $this->assertNotEmpty($response['created']);
        $this->assertEquals($params['role'], $response['role']);
        $this->assertEquals(Entity\User::STATUS_ACTIVE, $response['status']);

        $this->assertArrayNotHasKey('password', $response);
        $this->assertArrayNotHasKey('passwordHash', $response);
        $this->assertNotEmpty($response['token']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($response['id']);
        $this->assertNotEquals($params['password'], $user->getPassword());
    }

    /**
     * @param int $id
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderUpdate
     */
    public function testUpdate($id, $params = [], $errorMessage)
    {
        try {
            $response = $this->userService->update($id, $params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($params['email'], $response['email']);
        $this->assertEquals($params['firstName'], $response['firstName']);
        $this->assertEquals($params['lastName'], $response['lastName']);
        $this->assertNotEmpty($response['created']);
        $this->assertEquals($params['role'], $response['role']);
        $this->assertEquals(Entity\User::STATUS_ACTIVE, $response['status']);

        $this->assertArrayNotHasKey('password', $response);
        $this->assertArrayNotHasKey('passwordHash', $response);
        $this->assertNotEmpty($response['token']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($response['id']);
        $this->assertNotEquals($params['password'], $user->getPassword());
        $this->assertNotEquals($params['token'], $user->getToken());
    }

    /**
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @param string $errorMessage
     * @dataProvider dataProviderLogin
     */
    public function testLogin($email, $password, $remember, $errorMessage)
    {
        try {
            $response = $this->userService->login($email, $password, $remember);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($email, $response['email']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($response['id']);

        $this->assertEquals($user->getFirstName(), $response['firstName']);
        $this->assertEquals($user->getLastName(), $response['lastName']);
        $this->assertNotEmpty($response['lastLogin']);

        $this->assertArrayNotHasKey('password', $response);
        $this->assertArrayNotHasKey('passwordHash', $response);
        $this->assertNotEmpty($response['token']);
    }

    /**
     * @param string $email
     * @param string $token
     * @param bool $remember
     * @param string $errorMessage
     * @dataProvider dataProviderLoginSso
     */
    public function testLoginSso($email, $token, $remember, $errorMessage)
    {
        try {
            $response = $this->userService->loginSso($email, $token, $remember);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($email, $response['email']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($response['id']);

        $this->assertEquals($user->getFirstName(), $response['firstName']);
        $this->assertEquals($user->getLastName(), $response['lastName']);
        $this->assertNotEmpty($response['lastLogin']);

        $this->assertArrayNotHasKey('password', $response);
        $this->assertArrayNotHasKey('passwordHash', $response);
        $this->assertNotEmpty($response['token']);
    }

    /**
     * make sure enable and disable work
     */
    public function testEnableDisable()
    {
        $user = $this->createUser();
        $this->assertEquals(Entity\User::STATUS_ACTIVE, $user->getStatus());
        $this->userService->disable($user->getId());

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($user->getId());
        $this->assertEquals(Entity\User::STATUS_INACTIVE, $user->getStatus());
        $this->userService->enable($user->getId());

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneById($user->getId());
        $this->assertEquals(Entity\User::STATUS_ACTIVE, $user->getStatus());
    }

    /**
     * @param int $id
     * @param string $errorMessage
     * @dataProvider dataProviderResetToken
     */
    public function testResetToken($id, $errorMessage)
    {
        $tokenBefore = null;

        if (is_null($errorMessage)) {
            /** @var Entity\User $userBefore */
            $userBefore = $this->getServiceManager()->get('userRepository')->find($id);
            $tokenBefore = $userBefore->getToken();
        }

        try {
            $response = $this->userService->resetToken($id);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->findOneByToken($response['token']);

        $this->assertNotEquals($tokenBefore, $response['token']);
        $this->assertNotEquals($tokenBefore, $user->getToken());
    }

    /**
     * @param string $email
     * @param string $password
     * @param string $code
     * @param string $errorMessage
     * @dataProvider dataProviderUpdatePassword
     */
    public function testUpdatePassword($email, $password, $code, $errorMessage)
    {
        $userBefore = null;

        if (is_null($errorMessage)) {
            /** @var Entity\User $userBefore */
            $userBefore = $this->getServiceManager()->get('userRepository')->findOneByEmail($email);
            $userBefore = clone $userBefore;
        }

        try {
            $response = $this->userService->updatePassword($email, $password, $code);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayNotHasKey('password', $response);

        /** @var Entity\User $user */
        $user = $this->getServiceManager()->get('userRepository')->find($response['id']);

        $this->assertNotEquals(spl_object_hash($userBefore), spl_object_hash($user));
        $this->assertNotEquals($userBefore->getPassword(), $user->getPassword());
        $this->assertEquals($userBefore->getFirstName(), $user->getFirstName());
        $this->assertEquals($userBefore->getLastName(), $user->getLastName());
        $this->assertEquals($userBefore->getLastLogin(), $user->getLastLogin());
        $this->assertEquals($userBefore->getStatus(), $user->getStatus());
        $this->assertEquals($userBefore->getRole(), $user->getRole());
    }

    /**
     * @return array
     */
    public function dataProviderFindByEmail()
    {
        $user = $this->createUser();

        return [
            'invalid email' => [123, 'Invalid email address.'],

            'invalid user not found' => ['panda@powa'. uniqid() .'.com', 'User not found.'],

            'valid user' => [$user->getEmail(), null]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderCreate()
    {
        $user = $this->createUser();
        $newUser = $this->generateUser();

        return [
            'invalid email' => [[], 'Invalid email address.'],

            'invalid password' => [[
                'email' => 'panda@powa.com'
            ], 'Invalid password.'],

            'invalid first name' => [[
                'email' => 'panda@powa.com',
                'password' => 'testme'
            ], 'Invalid first name.'],

            'invalid last name' => [[
                'email' => 'panda@powa.com',
                'password' => 'testme',
                'firstName' => 'panda'
            ], 'Invalid last name.'],

            'duplicate user' => [[
                'email' => $user->getEmail(),
                'password' => 'testme',
                'firstName' => 'panda',
                'lastName' => 'powa'
            ], 'A user by that email address already exists.'],

            'valid' => [[
                'email' => $newUser->getEmail(),
                'password' => $newUser->getPassword(),
                'firstName' => $newUser->getFirstName(),
                'lastName' => $newUser->getLastName(),
                'role' => Entity\User::ROLE_ADMIN
            ], null]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderUpdate()
    {
        $user = $this->createUser();
        $newUser = $this->generateUser();

        return [
            'invalid email' => ['asdf', [], 'Invalid id.'],

            'invalid user not found' => [-1, [], 'User not found.'],

            'valid' => [$user->getId(), [
                'email' => $newUser->getEmail(),
                'password' => $newUser->getPassword(),
                'firstName' => $newUser->getFirstName(),
                'lastName' => $newUser->getLastName(),
                'role' => Entity\User::ROLE_ADMIN,
                'token' => 'newtoken'
            ], null]
        ];
    }

    /**
     * @return array
     */
    public function dataProviderLogin()
    {
        $user = $this->createUser();
        $user->setPassword('pandapowa');

        $disabledUser = $this->createUser();
        $disabledUser->setStatus(Entity\User::STATUS_INACTIVE);
        $disabledUser->setPassword('panda');

        $this->getEntityManager()->merge($user);
        $this->getEntityManager()->merge($disabledUser);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        return [
            'invalid email' => ['asdf', 'asdf', false, 'Invalid Login.'],

            'invalid email password combo' => [$user->getEmail(), 'asdf', false, 'Invalid Login.'],

            'invalid user disabled' => [$disabledUser->getEmail(), 'panda', false, 'User account is disabled.'],

            'valid' => [$user->getEmail(), 'pandapowa', false, null]

        ];
    }

    /**
     * @return array
     */
    public function dataProviderLoginSso()
    {
        $user = $this->createUser();

        $disabledUser = $this->createUser();
        $disabledUser->setStatus(Entity\User::STATUS_INACTIVE);

        $this->getEntityManager()->merge($user);
        $this->getEntityManager()->merge($disabledUser);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        return [
            //'invalid email' => ['asdf', 'asdf', false, 'Invalid Login.'], // not using email atm, just token

            'invalid token' => [$user->getEmail(), 'fakeone', false, 'Invalid Login.'],

            'invalid user disabled' => [
                $disabledUser->getEmail(),
                $disabledUser->getToken(),
                false,
                'User account is disabled.'
            ],

            'valid' => [$user->getEmail(), $user->getToken(), false, null]

        ];
    }

    /**
     * @return array
     */
    public function dataProviderResetToken()
    {
        $user = $this->createUser();

        return [
            'invalid email' => ['asdf', 'Invalid id.'],

            'invalid user not found' => [-1, 'User not found.'],

            'valid' => [$user->getId(), null]
        ];
    }

    /**
     * @todo move to integration test
     * @return array
     */
    public function dataProviderUpdatePassword()
    {
        $user = $this->createUser();
        $code = md5(uniqid() . $user->getEmail());
        $cacheKey = Service\User::PASSWORD_RESET_CACHE_PREFIX . $code;
        $newPassword = 'pandapowa' . uniqid();

        $this->getServiceManager()->get('redis')->set($cacheKey, $user->getEmail());
        // don't set expiration date atm as these run before all the tests run so might expire before the test runs.
        //$this->getServiceManager()->get('redis')->expire($cacheKey, 60);

        return [
            'invalid email' => ['asdf', null, null, 'Invalid email address.'],

            'invalid password' => [$user->getEmail(), 'asdf', null, 'Invalid password.'],

            'invalid code' => [$user->getEmail(), 'pandapowa', null, 'Invalid code.'],

            'invalid code not found' => [$user->getEmail(), 'pandapowa', 'asdfasdf', 'Invalid code.'],

            'valid' => [$user->getEmail(), $newPassword, $code, null]
        ];
    }

    /**
     * @todo move to functional helper
     */
    protected function createUser()
    {
        $user = $this->generateUser();

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        return $user;
    }
}
