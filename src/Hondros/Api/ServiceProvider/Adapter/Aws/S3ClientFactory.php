<?php

namespace Hondros\Api\ServiceProvider\Adapter\Aws;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Aws\S3\S3Client;

class S3ClientFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Aws\S3\S3Client
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')->aws;

        $s3 = new S3Client([
            'region'   => $config->region,
            'version'  => $config->version,
            'credentials' => [
                'key'    => $config->accessKeyId,
                'secret' => $config->secretAccessKey,
            ],
        ]);

        return $s3;
    }
}