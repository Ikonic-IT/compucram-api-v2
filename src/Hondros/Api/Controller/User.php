<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Hondros\Api\Model\Entity;
use Laminas\ServiceManager\ServiceManager;

class User implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * User constructor.
     * @param $serviceManager
     */
    public function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param Application $app
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/users', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'GET.USERS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->findAll($_GET));
        });

        $controllers->post('/users', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.USERS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->save($_POST));
        });

        $controllers->get('/users/{userId}', function ($userId) {
            $response  = null;
            if (!is_numeric($userId)) {
                $response = $this->serviceManager->get('userService')->findByEmail($userId, $_GET);
                $userId = $response['id'];
            }

            if ($this->serviceManager->get('user')->getRole() !== Entity\User::ROLE_ADMIN
                && (int) $this->serviceManager->get('user')->getId() !== (int) $userId) {
                throw new \Exception("Not authorized", 403);
            }

            if ($response) {
                return JsonResponse::create($response);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->find($userId, $_GET));
        });

        $controllers->put('/users/{userId}', function ($userId) {
            if (!is_numeric($userId)) {
                $response = $this->serviceManager->get('userService')->findByEmail($userId, $_GET);
                $userId = $response['id'];
            }

            if ($this->serviceManager->get('user')->getRole() !== Entity\User::ROLE_ADMIN
                && (int) $this->serviceManager->get('user')->getId() !== (int) $userId) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->update($userId, $_POST));
        });

        $controllers->post('/users/{userId}/enable', function ($userId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.USERS.ENABLE')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->enable($userId));
        });

        $controllers->post('/users/{userId}/disable', function ($userId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.USERS.DISABLE')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->disable($userId));
        });

        $controllers->get('/users/{userId}/enrollments', function ($userId) {
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->findForUser($userId, $_GET));
        });

        $controllers->get('/users/{userId}/logs', function ($userId) {
            return JsonResponse::create($this->serviceManager->get('userLogService')->findForUser($userId, $_GET));
        });

        $controllers->post('/users/{userId}/logs', function ($userId) {
            return JsonResponse::create($this->serviceManager->get('userLogService')->save($_POST));
        });

        $controllers->post('/users/{userId}/reset-token', function ($userId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.USERS.RESET_TOKEN')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('userService')->resetToken($userId));
        });
            
        return $controllers;
    }
}