<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/14/16
 * Time: 16:45 PM
 */

namespace Hondros\Api\Model\Listener;

use Doctrine\ORM\Event;
use Predis\Client as RedisClient;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\User as UserHydratorStrategy;
use Laminas\Config\Config;

class User extends ListenerAbstract
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
     * @var userHydratorStrategy
     */
    protected $userHydratorStrategy;

    /**
     * User constructor.
     * @param Config $config
     * @param RedisClient $redis
     * @param UserHydratorStrategy $strategy
     */
    public function __construct(Config $config, RedisClient $redis, UserHydratorStrategy $strategy)
    {
        $this->config = $config;
        $this->redis = $redis;
        $this->userHydratorStrategy = $strategy;
    }

    /**
     * @param Entity\User $user
     * @param Event\LifecycleEventArgs $event
     */
    public function postLoad(Entity\User $user, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $data = $this->userHydratorStrategy->getHydrator()->extract($user);
        $data = (new \Hondros\Common\Doctrine())->cleanUp($data);
        $this->redis->setex(
            Repository\User::CACHE_ID . $user->getId(),
            Repository\User::CACHE_TTL,
            json_encode($data));

        // check if already exits and set expiry if doesn't
        $tokenKey = Repository\User::CACHE_ID_TOKEN_HASH . substr($user->getToken(), 0, 1);
        $exist = $this->redis->exists($tokenKey);

        $this->redis->hset(
            $tokenKey,
            $user->getToken(),
            $user->getId());

        if (!$exist) {
            $this->redis->expire($tokenKey, Repository\User::CACHE_TTL_TOKEN_HASH);
        }
    }

    /**
     * @param Entity\User $user
     * @param Event\PreUpdateEventArgs $event
     */
    public function preUpdate(Entity\User $user, Event\PreUpdateEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // remove old token
        if ($event->hasChangedField('token')) {
            $tokenKey = Repository\User::CACHE_ID_TOKEN_HASH . substr($event->getOldValue('token'), 0, 1);
            $this->redis->hdel($tokenKey, $event->getOldValue('token'));
        }
    }

    /**
     * http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/events.html
     * @param Entity\User $user
     * @param Event\LifecycleEventArgs $event
     */
    public function postUpdate(Entity\User $user, Event\LifecycleEventArgs $event)
    {
        if (!$this->isEnabled()) {
            return;
        }

        // replace cache
        $this->postLoad($user, $event);
    }

    /**
     * @return bool
     */
    protected function isEnabled()
    {
        return (bool) $this->config->doctrine->listeners->usersListener;
    }
}