<?php

namespace Hondros\Api\Model\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query;
use Monolog\Logger;
use Predis\Client as Redis;
use Hondros\Api\Model\Repository\RepositoryAbstract;
use Laminas\Stdlib\Hydrator;
use Laminas\Config\Config;
use InvalidArgumentException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Hondros\Common\Collection;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

class ProgressQuestion extends RepositoryAbstract
{
    /**
    * @var Monolog\Logger
    */
    protected $logger;
    
    protected $redis;
    
    protected $config;
    
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class, Logger $logger, Redis $redis, Config $config) 
    {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->config = $config;
        
        parent::__construct($em, $class);
    }
    
//     public function getRandomIdsForProgress($progressId, $quantity, $params = [])
//     {
//         $dql = "
//             SELECT pq.id, q.id
//             FROM {$this->getEntityName()} pq
//             JOIN pq.question q
//             WHERE pq.progress = :progressId   
//         ";
        
//         // get all ids
//         $query = $this->getEntityManager()
//             ->createQuery($dql)
//             ->setParameter('progressId', $progressId);
        
//         $ids = $query->getArrayResult();
        
//         $randomIds = [];
        
//         // randomly select the quantity if more returned
//         while (count($randomIds) < $quantity && count($ids) > 0) {
//             $index = rand(0,count($ids) - 1);
//             $randomIds[] = $ids[$index]['id'];
//             array_splice($ids, $index, 1);
//         }
        
//         return $randomIds;
//     }

    public function findByProgressId($progressId, $params, $orderBy = [])
    {
        $dql = "SELECT pq FROM {$this->getEntityName()} pq ";

        // any joins?
        if (!empty($params['active'])) {
            $dql .= " JOIN pq.question q ";
        }

        $dql .= " WHERE pq.progress = :progressId ";

        // more conditions?
        if (isset($params['bookmarked'])) {
            $dql .= " AND pq.bookmarked = " . ((boolean) $params['bookmarked'] ? "true" : "false");
        }

        if (isset($params['answered'])) {
            $dql .= " AND pq.answered = " . ((boolean) $params['answered'] ? "true" : "false");
        }

        if (isset($params['correct'])) {
            $dql .= " AND pq.correct = " . ((boolean) $params['correct'] ? "true" : "false");
        }

        if (isset($params['viewed'])) {
            $dql .= " AND pq.viewed = :viewed ";
        }

        // only active questions?
        if (!empty($params['active'])) {
            $dql .= " AND q.active = true ";
        }

        if (!empty($orderBy)) {
            $dql .= " ORDER BY ";

            foreach ($orderBy as $key => $value) {
                switch (strtolower($key)) {
                    case 'viewed':
                        $dql .= "pq.viewed {$orderBy['viewed']}";
                        break;
                    default:
                }
            }
        }

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('progressId', $progressId);

        if (isset($params['viewed'])) {
            $query->setParameter('viewed', $params['viewed']);
        }

        return new Paginator($query, false);
    }
}