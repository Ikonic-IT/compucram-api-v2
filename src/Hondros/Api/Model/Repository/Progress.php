<?php

namespace Hondros\Api\Model\Repository;

use Monolog\Logger;
use Predis\Client as Redis;
use Hondros\Api\Model\Repository\RepositoryAbstract;
use Laminas\Config\Config;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\AbstractQuery;

class Progress extends RepositoryAbstract
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
    
    public function findByEnrollmentModule($enrollmentId, $moduleId, $type)
    {
        $dql = "
            SELECT p
            FROM Hondros\Api\Model\Entity\Progress p
            WHERE p.enrollment = {$enrollmentId} AND p.module = {$moduleId} AND p.type = :type
        ";
        
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('type', $type);
        
        return $query->getOneOrNullResult();
    }

    public function getQuestionBankPercentCorrectData($enrollmentId, $examId)
    {
        // there seems to be a bug where not all module attempts are saved / stored as expected
        // therefore also using the progress table here to alleviate some of that short-coming
        // it will not be exactly the same since there could one or more module attempt / practice test with different questions
        // but this better than not having it.
        $dql = "
        select
        (select count(*)
            from exam_prep.exam_module em 
                inner join exam_prep.module_question mq on em.module_id = mq.module_id
                inner join exam_prep.question q on mq.question_id = q.id
            where em.exam_id={$examId} and q.active = 1 and mq.type='practice') 'inQuestionBank',
        (select count(distinct qids.question_id)
            from (
                select question_id from exam_prep.module_attempt ma 
                    inner join exam_prep.module_attempt_question maq on maq.module_attempt_id = ma.id
                where ma.enrollment_id={$enrollmentId} and ma.type='practice' and maq.correct > 0
                union
                select question_id from exam_prep.progress p 
                    inner join exam_prep.progress_question pq on pq.progress_id = p.id
                where p.enrollment_id={$enrollmentId} and p.type='practice' and pq.correct > 0) qids) 'correctlyAnswered'
        ";
        
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('inQuestionBank', 'inQuestionBank');
        $rsm->addScalarResult('correctlyAnswered', 'correctlyAnswered');

        $query = $this->getEntityManager()->createNativeQuery($dql, $rsm);

        $data = $query->getOneOrNullResult();

        return $data;
    }

}