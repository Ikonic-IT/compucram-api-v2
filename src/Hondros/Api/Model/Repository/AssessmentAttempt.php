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

class AssessmentAttempt extends RepositoryAbstract
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
    
    /**
     * Finds all modules for an exam
     *
     * @param int $examId
     * @return array
     */
    public function findForEnrollment($enrollmentId, $params = [])
    {
        $dql = "
            SELECT aa
            FROM Hondros\Api\Model\Entity\AssessmentAttempt aa
            WHERE aa.enrollment = {$enrollmentId} ";
        
        if (!empty($params['type'])) {
            $dql .= "AND aa.type = '{$params['type']}' ";
        }
        
        $dql .= "ORDER BY aa.created DESC";
        $query = $this->getEntityManager()->createQuery($dql);
        
        return new Paginator($query, false);
    }
}
