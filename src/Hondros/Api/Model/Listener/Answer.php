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
use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Answer as AnswerHydratorStrategy;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Config\Config;

class Answer extends ListenerAbstract
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
     * @var AnswerHydratorStrategy
     */
    protected $answerHydratorStrategy;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    /**
     * Answer constructor.
     * @param Config $config
     * @param RedisClient $redis
     * @param AnswerHydratorStrategy $strategy
     * @param ServiceLocatorInterface $serviceManager
     */
    public function __construct(Config $config, RedisClient $redis, AnswerHydratorStrategy $strategy,
        ServiceLocatorInterface $serviceManager)
    {
        $this->config = $config;
        $this->redis = $redis;
        $this->answerHydratorStrategy = $strategy;
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
     * @param Entity\Answer $answer
     * @param Event\LifecycleEventArgs $event
     */
    public function postLoad(Entity\Answer $answer, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $data = $this->answerHydratorStrategy->getHydrator()->extract($answer);

        /** @todo can this part be added to the hydrator class methods? */
        $data = (new \Hondros\Common\Doctrine())->cleanUp($data);

        // @todo clean up this nasty hack
        unset($data['question']);
        unset($data['createdBy']);
        unset($data['modifiedBy']);

        $this->redis->set(Repository\Answer::CACHE_ID . $answer->getId(), json_encode($data));

        // make sure they are in their question bank set
        // @todo make sure calling get question doesn't create a db query
        $this->redis->sadd('set:question:' . $answer->getQuestionId() . ':answers', $answer->getId());
    }

    /**
     * @param Entity\Answer $answer
     * @param Event\PreUpdateEventArgs $event
     */
    public function preUpdate(Entity\Answer $answer, Event\PreUpdateEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->setModified($answer);
    }

    /**
     * @todo pre update, don't update if nothing changed
     * http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/events.html
     * @param Entity\Answer $answer
     * @param Event\LifecycleEventArgs $event
     */
    public function postUpdate(Entity\Answer $answer, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // replace cache
        $this->postLoad($answer, $event);
        $this->getQuestionMessageQueue()->addQuestionToElasticsearchQueue($answer->getQuestionId());
    }

    /**
     * @param Entity\Answer $answer
     * @param Event\LifecycleEventArgs $event
     */
    public function prePersist(Entity\Answer $answer, Event\LifecycleEventArgs $event)
    {
        $this->setCreatedAt($answer);
        $this->setModified($answer);
    }

    /**
     * @param Entity\Answer $answer
     * @param Event\LifecycleEventArgs $event
     */
    public function postPersist(Entity\Answer $answer, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // make sure all ids are set correctly
        $answer->setQuestionId($answer->getQuestion()->getId());

        // remove these for now so the code is smart enough to regenerate it all when not found
        $this->postLoad($answer, $event);
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        return (bool) $this->config->doctrine->listeners->answerListener;
    }

    /**
     * @param Entity\Answer $answer
     */
    protected function setModified(Entity\Answer $answer)
    {
        $answer->setModified(new \DateTime());

        try {
            $user = $this->serviceManager->get('user');
        } catch (ServiceNotFoundException $e) {
            return;
        }

        $answer->setModifiedById($user->getId());
        $answer->setModifiedBy($this->serviceManager->get('entityManager')
            ->getReference('Hondros\Api\Model\Entity\User', $user->getId()));
    }

    /**
     * @param Entity\Answer $answer
     */
    protected function setCreatedAt(Entity\Answer $answer)
    {
        $answer->setCreated(new \DateTime());

        try {
            $user = $this->serviceManager->get('user');
        } catch (ServiceNotFoundException $e) {
            return;
        }

        $answer->setCreatedById($user->getId());
        $answer->setCreatedBy($this->serviceManager->get('entityManager')
            ->getReference('Hondros\Api\Model\Entity\User', $user->getId()));
    }
}