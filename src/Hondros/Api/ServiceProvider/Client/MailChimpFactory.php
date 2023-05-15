<?php

namespace Hondros\Api\ServiceProvider\Client;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Client;
use Hondros\Api\Service;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MailChimpFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')->mailChimp;
        $client = new \GuzzleHttp\Client([
            'base_uri' => $config->uri,
            'auth' => [
                $config->auth->username,
                $config->auth->apiKey
            ]
        ]);

        return new Client\MailChimp($client,
            $container->get('logger'));

    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}