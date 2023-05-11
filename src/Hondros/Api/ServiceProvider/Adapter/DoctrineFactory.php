<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Hondros\ThirdParty\Doctrine\Common\Cache\QueryPredisCache;
use Doctrine\Common\Cache\PredisCache;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\Common\Cache\ArrayCache;

use Monolog\Handler\ChromePHPHandler;
use Hondros\ThirdParty\Doctrine\DBAL\Logging\ChromeSQLLogger;

use Predis\Client as RedisClient;

class DoctrineFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $paths = array(realpath($serviceLocator->get('config')->doctrine->paths->entity));
        $proxyPath = $serviceLocator->get('config')->doctrine->paths->proxy;
        $isDevMode = $serviceLocator->get('config')->environment == 'development' ? true : false;

        $cacheAdapter = new ArrayCache();
        $queryAdapter = new ArrayCache();

        // change to a switch and see what type of cache we should enable based on server but allow the main ones
        if ($serviceLocator->get('config')->doctrine->cache->enabled) {
            try {
                $predis = new RedisClient(
                    (array)$serviceLocator->get('config')->redis->toArray(), 
                    array('prefix' => $serviceLocator->get('config')->doctrine->cache->prefix)
                );
                $cacheAdapter = new PredisCache($predis);
                $queryAdapter = new QueryPredisCache($predis);
                $queryAdapter->setLifeTime($serviceLocator->get('config')->doctrine->cache->queryLifeTime);
            } catch (\Exception $e) {
                throw new \Exception("Redis connection failure " . $e->getMessage());
            }
        }

        // setup required configs
        $config = new Configuration();
        $config->setMetadataCacheImpl($cacheAdapter);
        $config->setQueryCacheImpl($queryAdapter);
        $config->setResultCacheImpl($cacheAdapter);
        $config->setProxyDir($proxyPath);
        $config->setProxyNamespace('DoctrineProxies');
        $config->setAutoGenerateProxyClasses($isDevMode);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths, false));

        // register event listeners - even if we don't want to use them. They'll check inside if they need to do anything
        foreach ($serviceLocator->get('config')->doctrine->listeners as $key => $value) {
            $listenerClass = $serviceLocator->get($key);
            $config->getEntityListenerResolver()->register($listenerClass);
        }

        $entityManager = EntityManager::create($serviceLocator->get('config')->doctrine->params->toArray(), $config);

        // update to be based on config file
        if ($serviceLocator->get('config')->debug->queries) {
            // setup log
            $chromeLogger = new ChromeSQLLogger();
            $chromeLogger->setLog($serviceLocator->get('logger'));
            $entityManager->getConnection()->getConfiguration()->setSQLLogger($chromeLogger);
        }

        // allow mysql enum type
        $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        return $entityManager;
    }
}