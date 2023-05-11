<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 11/27/15
 * Time: 11:10 AM
 */

namespace Hondros\Api\Model\Listener;

use Doctrine\ORM\Event;
use Predis\Client as RedisClient;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Question as QuestionHydratorStrategy;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Config\Config;

class ModuleQuestion extends ListenerAbstract
{
    /**
     * @var Config;
     */
    protected $config;

    /**
     * @var RedisClient
     */
    protected $redis;

    /**
     * @var QuestionHydratorStrategy
     */
    protected $questionHydratorStrategy;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    /**
     * Question constructor.
     * @param Config $config
     * @param RedisClient $redis
     * @param QuestionHydratorStrategy $strategy
     * @param ServiceLocatorInterface $serviceManager
     */
    public function __construct(Config $config, RedisClient $redis, QuestionHydratorStrategy $strategy,
                                ServiceLocatorInterface $serviceManager)
    {
        $this->config = $config;
        $this->redis = $redis;
        $this->questionHydratorStrategy = $strategy;
        $this->serviceManager = $serviceManager;
    }

    /**
     * @return \Hondros\Api\MessageQueue\Question
     */
    protected function getQuestionMessageQueue()
    {
        return $this->serviceManager->get('questionMessageQueue');
    }

    /**
     * After a module question is created, add to cache and need to update progress?
     * @param Entity\ModuleQuestion $moduleQuestion
     * @param Event\LifecycleEventArgs $event
     * @todo reuse question queue or create new one for module question?
     * @todo probably need to turn off listeners when importing content as there won't be any progresses
     */
    public function postPersist(Entity\ModuleQuestion $moduleQuestion, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // now that we added a new question to a module, we need to add the question to progresses
        $this->getQuestionMessageQueue()->addModuleQuestionToAddedNewQueue(
            $moduleQuestion->getModuleId(),
            $moduleQuestion->getType(),
            $moduleQuestion->getQuestionId()
        );

        $this->postLoad($moduleQuestion, $event);
    }

    /**
     * @param Entity\ModuleQuestion $moduleQuestion
     * @param Event\LifecycleEventArgs $event
     */
    public function postLoad(Entity\ModuleQuestion $moduleQuestion, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $cacheId = "set:module:{$moduleQuestion->getModuleId()}:type:{$moduleQuestion->getType()}:questions";
        $this->redis->sadd($cacheId, $moduleQuestion->getQuestionId());
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        return (bool) $this->config->doctrine->listeners->moduleQuestionListener;
    }
}