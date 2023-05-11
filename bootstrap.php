<?php

chdir(__DIR__);

$loader = require_once "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

use Hondros\Api\Controller;
use Symfony\Component\HttpFoundation\Request;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Knp\Provider\ConsoleServiceProvider;

// init service manager
$serviceManager = new \Laminas\ServiceManager\ServiceManager(require 'config/servicemanager.php');
$config = \Hondros\Api\Util\Config::init(defined('PHPUNIT') && PHPUNIT);
$serviceManager->setService('config', $config);
$serviceManager->setService('user', (new Hondros\Api\Model\Entity\User())
    ->setRole(\Hondros\Api\Model\Entity\User::ROLE_GUEST));

$app = new Silex\Application();
$app['debug'] = (boolean)$serviceManager->get('config')->debug->application;
$app['user'] = null;

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Compucram CLI Tools',
    'console.version'           => '1.0.0',
    'console.project_directory' => getcwd()
));

// cors
if ((boolean)$serviceManager->get('config')->cors->enabled) {
    // create space delimited list of domains
    $domains = implode(' ', $serviceManager->get('config')->cors->domains->toArray());

    $app->register(new CorsServiceProvider(), array(
        "cors.allowOrigin" => $domains,
    ));

    $app->after($app['cors']);
}

// check for token before we move forward
$app->before(function(Request $request) use ($app, $serviceManager) {
    // authenticate with token
    $path = $request->getPathInfo();
    $whitelist = [
        '/auth/login',
        '/auth/login-sso',
        '/auth/logout',
        '/auth/request-password-reset',
        '/auth/update-password'
    ];

    // do we need a token?
    if (!in_array($path, $whitelist)) {
        if (empty($_GET['token'])) {
            throw new \Exception("Invalid token specified.", 401);
        }

        // load user from token to validate
        $user = $serviceManager->get('userRepository')->findOneByToken($_GET['token']);

        if (empty($user)) {
            throw new \Exception("Invalid token specified.", 401);
        }

        // store authenticated user with roles/permissions
        $serviceManager
            ->setAllowOverride(true)
            ->setService('user', $user)
            ->setAllowOverride(false);

        // remove token
        unset($_GET['token']);
    }

    /**
     * check for content type, we might need to manipulate the data
     *
     * we are updating $_POST if needed for post and put
     * @todo use a more appropriate variables instead of $_POST moving forward
     *   specially since using $_POST for PUT sounds confusing in the controllers
     */
    $contentType = $request->getContentType();
    $httpMethod = $request->getMethod();
    $content = $request->getContent();

    // double check http spec but I think delete shouldn't take in params
    if (($httpMethod === 'PUT' || $httpMethod === 'POST') && !empty($content)) {
        switch ($contentType) {
            case 'json':
                $_POST = json_decode($content, true);
                break;

            case 'form':
                $_POST = $request->request->all();
                break;

            default:
                // note - silex doesn't seem to support multipart/form-data out of the box
                throw new \Exception("Invalid Content-Type specified {$contentType}. application/json and " .
                    "application/x-www-form-urlencoded are supported.");
        }
    }
});

//$app->after(function(Request $request, Response $response) use ($app) {});

/**
 * display errors how we want them, can log here as well if needed. This is
 * called for any exception. Typically used for 400-500 errors
 * @todo clean up, check for the correct exception
 */
$app->error(function (\Exception $e, $code) use ($app, $serviceManager) {
    $message = $e->getMessage();
    $debug = (boolean) $serviceManager->get('config')->debug->application;

    if ($debug) {
        $message = $e->getFile() . ',' . $e->getLine() . ',' . $message;
    }

    // need to return valid http error code versus internal error code
    $code = (int) $e->getCode() ?: 500;
    $internalCode = $e->getCode();

    if ($code < 400 || $code >= 600) {
        $code = 500;
    }

    // debug code to see all exceptions
    if ($debug) {
        while ($e = $e->getPrevious()) {
            $message .= sprintf("%s:%d %s (%d) [%s]\n", $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode(), get_class($e));
        }
    }

    return $app->json([
        'error' => $message,
        'code' => $internalCode
    ], $code);
});

// mount points for sub calls
$app->mount('/', new Controller\Answer($serviceManager));
$app->mount('/', new Controller\AssessmentAttempt($serviceManager));
$app->mount('/', new Controller\AssessmentAttemptQuestion($serviceManager));
$app->mount('/', new Controller\Auth($serviceManager));
$app->mount('/', new Controller\Enrollment($serviceManager));
$app->mount('/', new Controller\Exam($serviceManager));
$app->mount('/', new Controller\Industry($serviceManager));
$app->mount('/', new Controller\Module($serviceManager));
$app->mount('/', new Controller\ModuleQuestion($serviceManager));
$app->mount('/', new Controller\ModuleAttempt($serviceManager));
$app->mount('/', new Controller\ModuleAttemptQuestion($serviceManager));
$app->mount('/', new Controller\Organization($serviceManager));
$app->mount('/', new Controller\Question($serviceManager));
$app->mount('/', new Controller\Report($serviceManager));
$app->mount('/', new Controller\State($serviceManager));
$app->mount('/', new Controller\User($serviceManager));
$app->mount('/', new Controller\ReadinessScore($serviceManager));
//$app->mount('/', new Controller\Import($serviceManager)); don't need atm, add when we build admin tools

// @todo remove question bank after new module question implementation
//$app->mount('/', new Controller\QuestionBank($serviceManager));

return $app;