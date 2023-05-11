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

class ModuleAttemptQuestion extends RepositoryAbstract
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
     * @todo need to figure out how to get total views passed down
     */
    public function findLatestForEnrollmentModule($enrollmentId, $moduleId, $params = [])
    {
        if (empty($params['type'])) {
            throw new InvalidArgumentException("Type must be submitted.");
        }
        
        $sql = "";
        $join = "";
        $having = "";
        
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('Hondros\Api\Model\Entity\ModuleAttemptQuestion', 'u', [
            'created' => 'userCreated',
            'modified' => 'userModified'
        ]);
        
        // don't use original viewed, use the sum
        $rsm->addFieldResult('u', 'total_views', 'viewed');
        
        // do we need questions?
        if (!empty($params['includes']) && in_array('question', $params['includes'])) {
            $rsm->addJoinedEntityFromClassMetadata('Hondros\Api\Model\Entity\Question', 'q', 'u', 'question', array(
                'id' => 'questionId',
                'question_id' => 'questionId',
                'correct' => 'questionCorrect',
                'created' => 'questionCreated',
                'modified' => 'questionModified'
            ));
            $rsm->addJoinedEntityFromClassMetadata('Hondros\Api\Model\Entity\Answer', 'an', 'q', 'answers', array(
                'id' => 'answerId',
                'question_id' => 'answerQuestionId',
                'correct' => 'answerCorrect',
                'created' => 'answerCreated',
                'modified' => 'answerModified'
            ));
        
            $join .= "
                INNER JOIN question q ON a.question_id = q.id
                INNER JOIN answer an ON q.id = an.question_id
            ";
        }
        
        // do we only want bookmarked?
        if (!empty($params['bookmarked']) && $params['bookmarked'] == true) {
            $having .= "HAVING bookmarked > 0 ";
        }
        
        $selectClause = $rsm->generateSelectClause(array(
            'u' => 'a',
        ));
        
        // get all question id's for the module/type
        $sql = "select {$selectClause}, b.total_views
            from module_attempt_question a
            JOIN (
             select max(id) as id, count(question_id) as total_views
                from module_attempt_question
                where module_attempt_id in (
                    select id
                    from module_attempt
                    where enrollment_id = ?
                        and module_id = ?
                        and type = ?
                ) AND modified IS NOT NULL
                group by question_id
            ) b ON a.id = b.id
        " . $join . $having;
        
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm)
            ->setParameter(1, $enrollmentId)
            ->setParameter(2, $moduleId)
            ->setParameter(3, $params['type']);
        
        return $query->getResult();
    }
    
    public function findQuestionIdsForModuleAttempt($moduleAttemptId)
    {
        $dql = "
            SELECT maq.questionId
            FROM Hondros\Api\Model\Entity\ModuleAttemptQuestion maq
            WHERE maq.moduleAttempt = :moduleAttemptId    
        ";
        
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('moduleAttemptId', $moduleAttemptId);
        
        return $query->getArrayResult();
    }
}




