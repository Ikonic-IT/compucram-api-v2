<?php

/**
* Used as local override. rename to local.php to use
* Just add any key value from the config.php to replace value
*/
 
return array(
    'environment' => 'developmentfake',
    
    'debug' => array(
        'application' => true,
        'queries' => false
    ),
    
    'examPrepApp' => array(
        'baseUrl' => 'http://d2-app.compucram.com',
        'logoUrl' => 'http://d2-app.compucram.com/assets/img/compucram_logo.svg',
        'resetUrl' => 'http://d2-app.compucram.com/#/login/update-password',
    ),
    
    'doctrine' => array(
        'params' => array(
            'driver'   => 'pdo_mysql',
            'host'     => 'dev-db01-instance-1.cspgv40qaxgt.us-east-1.rds.amazonaws.com',
            'user'     => 'admin',
            'password' => 'K87klsadf87238!',
            'dbname'   => 'exam_prep_phpunit'
        ),
        'cache' => array(
            'enabled' => true,
            'queryLifeTime' => 30
        )
    ),
    
    'redis' => array(
        'host' => '10.201.2.56',
        'password' => 'X94etRcmQOX2'
    ),

    'elasticsearch' => array(
        'client' => array(
            'hosts' => array(
                '10.201.2.56'
            )
        )
    ),
    
    'rabbit' => array (
        'host' => '10.201.2.56',
        'user' => 'guest',
        'password' => '3tJMnV5FuCpI',
        'vhost' => 'phpunit'
    ),

    'rackspace' => array (
        'username' => 'joey.rivera',
        'apiKey' => '91858d7ddaaf47f78f5bcc9480364ef2',
        'region' => 'DFW',
        'containers' => array (
            'content' => array (
                'export' => 'CompucramContentAdminDev'
            )
        )
    ),

    'audio' => array(
        'file' => array(
            'path' => 'C:\Users\Joey\Documents\htdocs\contract\hondros\audio'
        )
    ),

    'mailChimp' => array(
        'uri' => 'https://us11.api.mailchimp.com/3.0/',
        'auth' => array(
            'username' => 'joey1.rivera@gmail.com',
            'apiKey' => 'b9447814d33fe604c392c9e820b8c29e-us11'
        ),
        'list' => array(
            'trial' => '7499c4f971',
            'converted' => '810ac1886b',
            'enrolled' => '309b889d23',
            'pendingPayment' => '5b39d653de'
        )
    )
);
