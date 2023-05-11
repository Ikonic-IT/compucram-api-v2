<?php

return array(
    'version' => '1.2.1',
    'environment' => '',
    'loginCookie' => 1209600, // 14 days in seconds
    
    'debug' => array(
        'application' => false,
        'queries' => false
    ),
    
    'examPrepApp' => array(
        'baseUrl' => '',
        'logoUrl' => '',
        'resetUrl' => '',
    ),
    
    'log' => array(
        'file' => array(
            'path' => 'logs/main.log',
        )
    ),
    
    'cors' => array (
        'enabled' => true,
        'domains' => array(
            
        )
    ),
    
    'doctrine' => array(
        'paths' => array(
            'entity' => 'src/Hondros/Api/Model/Entity',
            'proxy' => 'src/Hondros/Api/Model/Proxy'
        ),
        'params' => array(
            'driver'   => 'pdo_mysql',
            'host'     => '',
            'user'     => '',
            'password' => '',
            'dbname'   => '',
            'charset'  => 'utf8'
        ),
        'cache' => array(
            'enabled' => false,
            'prefix' => 'hondros:api:doctrine:',
            'queryLifeTime' => 1800
        ),
        'listeners' => array(
            'questionListener' => true,
            'answerListener' => true,
            'userListener' => true,
            'moduleQuestionListener' => true
        )
    ),

    'elasticsearch' => array(
        'client' => array(
            'hosts' => array(
                ''
            )
        )
    ),
    
    'redis' => array(
        'enabled' => true,
        'scheme' => 'tcp',
        'host' => '',
        'port' => '6379',
        'prefix' => 'hondros:api:',
        'persistent' => 1
    ),
    
    'rabbit' => array (
        'enabled' => true,
        'host' => '',
        'port' => 5672,
        'user' => '',
        'password' => '',
        'vhost' => 'compucram'
    ),

    'aws' => [
        'region' => 'us-east-1',
        'version' => 'latest',
        'accessKeyId' => '',
        'secretAccessKey' => ''
    ],
    
    'import' => array(
        'examPath' => '/content/import/exams',
        'modulePath' => '/content/import/modules',
        'userPath' => '/content/import/users'
    ),

    'audio' => array(
        'textToSpeech' => array(
            'apiUrl' => 'http://tts-api.com/tts.mp3'
        ),
        'file' => array(
            'format' => 'mp3',
            'path' => ''
        )
    ),
    
    'email' => array (
        'passwordReset' => array(
            'templatePath' => '/content/email/passwordReset.html',
            'sender' => 'support@compucram.com',
            'subject' => 'Password Reset',
            'codeExpiration' => 7200
        )    
    ),

    'mailChimp' => array(
        'uri' => '',
        'auth' => array(
            'username' => '',
            'apiKey' => ''
        ),
        'list' => array(
            'trial' => '',
            'converted' => '',
            'enrolled' => '',
            'pendingPayment' => ''
        )
    ),

    'crontab' => array (
        'progress' => array(
            'recalculate' => array(
                'ttl' => 600,
                'consumers' => 3
            ),
            'addQuestion' => array(
                'ttl' => 600,
                'consumers' => 3
            )
        ),
        'question' => array(
            'addedNew' => array(
                'ttl' => 600,
                'consumers' => 1
            ),
            'statusChange' => array(
                'ttl' => 600,
                'consumers' => 1
            ),
            'addToElasticsearch' => array(
                'ttl' => 600,
                'consumers' => 2
            )
        ),
        'moduleAttempt' => array(
            'recalculate' => array(
                'ttl' => 600,
                'consumers' => 1
            )
        )
    )

);