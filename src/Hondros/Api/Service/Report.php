<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 07/31/2019
 * Time: 8:01 PM
 */

namespace Hondros\Api\Service;

use Hondros\Api\Console\Job\Report\QuestionStats;
use Hondros\Api\Console\Job\Report\TotalStats;
use Hondros\Common\Collection;
use Predis\Client;

class Report
{
    /**
     * @var Predis\Client
     */
    protected $cacheAdapter;

    public function __construct(Client $cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    public function getApplicationStats()
    {
        return new Collection($this->cacheAdapter->hgetall(TotalStats::CACHE_ID));
    }

    public function getQuestionStats()
    {
        return new Collection($this->cacheAdapter->hgetall(QuestionStats::CACHE_ID));
    }

}