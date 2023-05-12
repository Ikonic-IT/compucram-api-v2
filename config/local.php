<?php

/**
* Used as local override. rename to local.php to use
* Just add any key value from the global.php to replace value
*/
 
return array(
    'debug' => array(
        'application' => true,
        'queries' => false
    ),
    'cors' => array (
        'enabled' => true,
        'domains' => array(
            '*'
        )
    ),
    'examPrepApp' => array(
        'baseUrl' => 'https://d-app8.compucram.com',
        'logoUrl' => 'https://d-app8.compucram.com/assets/img/compucram_logo.png',
        'resetUrl' => 'https://d-app8.compucram.com/#/login/update-password',
    ),
    'doctrine' => array(
        'params' => array(
            'driver'   => 'pdo_mysql',
            'host'     => 'dev-prodcopy-80.cluster-cspgv40qaxgt.us-east-1.rds.amazonaws.com',
            'user'     => 'admin',
            'password' => 'lVM2tGyP5PB5iRmQ',
            'dbname'   => 'exam_prep',
            'charset'  => 'utf8'
        ),
    ),
    'redis' => array(
        'host' => 'dev-compucram.ovsrjb.ng.0001.use1.cache.amazonaws.com',
        'password' => ''
    ),
    'rabbit' => array (
        'host' => 'b-95948bce-6b44-4dc8-b611-e36172eca75c.mq.us-east-1.amazonaws.com',
        'port' => '5671',
        'user' => 'adminrbmq',
        'password' => 'MkJLCG.JjR9F@KKHG',
	'ssl_protocol' => 'ssl',
	'ssl_options' => array(
            'ssl_on' =>  true,
            'ssl_verify' => false
        ),
    ),
    'elasticsearch' => array(
        'client' => array(
            'hosts' => array(
                'vpc-dev-compucram-zepd3yx2mp5lsmo43h4iotirs4.us-east-1.es.amazonaws.com:80'
            )
        )
    ),
    'mailChimp' => array(
        'uri' => 'https://us11.api.mailchimp.com/3.0/',
        'auth' => array(
            'username' => 'joey1.rivera@gmail.com',
            'apiKey' => ''
        ),
        'list' => array(
            'trial' => '',
            'converted' => '',
            'pendingPayment' => '',
            'enrolled' => ''
        )
    ),
    'aws' => [
        'accessKeyId' => 'AKIA4BHJVAG26NXYU2A7',
        'secretAccessKey' => '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',
        's3' => [
            'exportContent' => 'cc-admin-stage'
        ]
    ]
);
