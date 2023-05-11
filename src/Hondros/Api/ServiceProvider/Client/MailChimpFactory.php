<?php

namespace Hondros\Api\ServiceProvider\Client;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Client;

class MailChimpFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')->mailChimp;
        $client = new \GuzzleHttp\Client([
            'base_uri' => $config->uri,
            'auth' => [
                $config->auth->username,
                $config->auth->apiKey
            ]
        ]);

        return new Client\MailChimp($client,
            $serviceLocator->get('logger'));

    }
}