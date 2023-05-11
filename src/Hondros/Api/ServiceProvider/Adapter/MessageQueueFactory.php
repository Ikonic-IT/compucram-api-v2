<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use PhpAmqpLib\Connection\AMQPSSLConnection;

class MessageQueueFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('config')->rabbit;

        // https://stackoverflow.com/questions/64888592/cant-connect-to-rabbitmq-on-amazon-mq
        // https://github.com/php-amqplib/php-amqplib/issues/757
		return new AMQPSSLConnection(
			$options->host,
			$options->port,
			$options->user,
			$options->password,
			$options->vhost,
                        [
                            'verify_peer' => false
                        ]
		);
    }
}
