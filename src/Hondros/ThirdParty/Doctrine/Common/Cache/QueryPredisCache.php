<?php

namespace Hondros\ThirdParty\Doctrine\Common\Cache;

use Doctrine\Common\Cache;

/**
 * Created this class to extend doSave and replace the 0 ttl for caching query cache.
 * Can't find any other way to make Doctrine not add query caching and not expire it
 *
 * Class QueryPredisCache
 * @package Hondros\ThirdParty\Doctrine\Common\Cache
 */
class QueryPredisCache extends Cache\PredisCache
{
    protected $lifeTime = 0;

    public function setLifeTime($lifeTime = 0)
    {
        $this->lifeTime = $lifeTime;

        return $this;
    }

    /**
     * To limit TTL for query cache, else no ttl is set
     * @param string $id
     * @param string $data
     * @param int $lifeTime
     * @return bool
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        return parent::doSave($id, $data, $this->lifeTime);
    }
}