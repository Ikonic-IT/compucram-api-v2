<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class MessageQueueFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('config')->rabbit;

		return new AMQPStreamConnection(
			$options->host,
			$options->port,
			$options->user,
			$options->password,
			$options->vhost
		);
    }
}