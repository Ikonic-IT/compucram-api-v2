<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Elasticsearch\ClientBuilder;

class ElasticsearchFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Elasticsearch\Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')->elasticsearch->client;

        return ClientBuilder::fromConfig($config->toArray());
    }
}