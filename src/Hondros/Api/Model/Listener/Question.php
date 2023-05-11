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

class Question extends ListenerAbstract
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
     * @param Entity\Question $question
     * @param Event\LifecycleEventArgs $event
     */
    public function postLoad(Entity\Question $question, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $data = $this->questionHydratorStrategy->getHydrator()->extract($question);

        /** @todo can this part be added to the hydrator class methods? */
        $data = (new \Hondros\Common\Doctrine())->cleanUp($data);

        // @todo clean up this nasty hack, need a better way to hydrate/extract
        unset($data['questionBank']);
        unset($data['answers']);
        unset($data['createdBy']);
        unset($data['modifiedBy']);

        $this->redis->set(Repository\Question::CACHE_ID . $question->getId(), json_encode($data));
    }

    /**
     * @todo don't update if nothing changed
     * @param Entity\Question $question
     * @param Event\PreUpdateEventArgs $event
     */
    public function preUpdate(Entity\Question $question, Event\PreUpdateEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        /**
         * If the question was marked active/inactive, we need to queue it up
         */
        if ($event->hasChangedField('active')) {
            $this->getQuestionMessageQueue()->addQuestionToStatusUpdateQueue($question->getId());
        }

        $this->setModified($question);
    }

    /**
     * @param Entity\Question $question
     * @param Event\LifecycleEventArgs $event
     */
    public function prePersist(Entity\Question $question, Event\LifecycleEventArgs $event)
    {
        $this->setCreatedAt($question);
        $this->setModified($question);
    }

    /**
     * http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/events.html
     * @param Entity\Question $question
     * @param Event\LifecycleEventArgs $event
     */
    public function postUpdate(Entity\Question $question, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // replace cache
        $this->postLoad($question, $event);
        $this->getQuestionMessageQueue()->addQuestionToElasticsearchQueue($question->getId());
    }

    /**
     * After a question is created, add to cache and es
     * @param Entity\Question $question
     * @param Event\LifecycleEventArgs $event
     */
    public function postPersist(Entity\Question $question, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->getQuestionMessageQueue()->addQuestionToElasticsearchQueue($question->getId());

        if (empty($question->getQuestionBankId())) {
            return $this->postLoad($question, $event);
        }

        // make sure all ids are set correctly
        $question->setQuestionBankId($question->getQuestionBank()->getId());

        // remove these for now so the code is smart enough to regenerate it all when not found
        $this->redis->sadd('set:questionBank:' . $question->getQuestionBankId() . ':questions', $question->getId());
        $this->postLoad($question, $event);
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        return (bool) $this->config->doctrine->listeners->questionListener;
    }

    /**
     * @param Entity\Question $question
     */
    protected function setModified(Entity\Question $question)
    {
        $question->setModified(new \DateTime());

        try {
            $user = $this->serviceManager->get('user');
        } catch (ServiceNotFoundException $e) {
            return;
        }

        $question->setModifiedById($user->getId());
        $question->setModifiedBy($this->serviceManager->get('entityManager')
            ->getReference('Hondros\Api\Model\Entity\User', $user->getId()));
    }

    /**
     * @param Entity\Question $question
     */
    protected function setCreatedAt(Entity\Question $question)
    {
        $question->setCreated(new \DateTime());

        try {
            $user = $this->serviceManager->get('user');
        } catch (ServiceNotFoundException $e) {
            return;
        }

        $question->setCreatedById($user->getId());
        $question->setCreatedBy($this->serviceManager->get('entityManager')
            ->getReference('Hondros\Api\Model\Entity\User', $user->getId()));
    }
}