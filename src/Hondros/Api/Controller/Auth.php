<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Auth implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Auth constructor.
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
        
        /**
         * @OA\Post(
         *     path="/auth/login",
         *     summary="Get a user login",
         *     description="Returns  user details",
         *     @OA\Response(
         *         response=200,
         *         description="Successful operation",
         *         @OA\JsonContent(
         *             type="array",
         *             @OA\Items(ref="#/Hondros/Api/Model/Listener/User")
         *         )
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="Unauthorized"
         *     ),
         * )
         */
        $controllers->post('/auth/login', function () {
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;

            $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
        
            $remember = !empty($_POST['remember']) ? $_POST['remember'] : false;
            
            return JsonResponse::create($this->serviceManager->get('userService')->login($email, $password, $remember));
        });
        
        $controllers->post('/auth/login-sso', function () {
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $token = !empty($_POST['token']) ? trim($_POST['token']) : null;
            
            return JsonResponse::create($this->serviceManager->get('userService')->loginSso($email, $token));
        });
            
        $controllers->post('/auth/logout', function () {
            return JsonResponse::create($this->serviceManager->get('userService')->logout());
        });
        
        $controllers->post('/auth/request-password-reset', function () {
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            
            return JsonResponse::create($this->serviceManager->get('userService')->requestPasswordReset($email));
        });
        
        $controllers->post('/auth/update-password', function () {
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
            $code = !empty($_POST['code']) ? trim($_POST['code']) : null;
            
            return JsonResponse::create($this->serviceManager->get('userService')->updatePassword($email, $password, $code));
        });
            
        return $controllers;
    }
}