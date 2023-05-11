<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 1/24/2015
 * Time: 10:36 PM
 */

namespace Hondros\Api\Console\Job\Content;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class deleteEnrollments extends Command
{
    protected $serviceManager;

    /**
     * @return mixed
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return ImportExam
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return \Hondros\Api\Model\Repository\Enrollment
     */
    public function getEnrollmentRepository()
    {
        return $this->getServiceManager()->get('enrollmentRepository');
    }

    protected function configure()
    {
        $this->setName("content:deleteEnrollments")
            ->setDescription("Removes enrollment data older than 2017");
    }

    /**
     * @todo take in a date to use as the starting enrollment
     * @todo make sure this only works in dev
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return false;
        $output->writeln("Deleting enrollments");

        $queryBuilder = $this->getEnrollmentRepository()->createQueryBuilder('e');
        $queryBuilder->select($queryBuilder->expr()->count('e.id'));

        // enrollment 64464
        // user log 15160985
        $deleteSql = '
            DELETE FROM enrollment
            WHERE id < 64464
            ORDER BY id asc
            LIMIT 10
        ';

        $countQuery = $queryBuilder->getQuery();
        $deleteStmt = $this->getServiceManager()->get('entityManager')->getConnection()->prepare($deleteSql);

        $lastCount = 0;
        $thisCount = $countQuery->getSingleScalarResult();

        while ($lastCount != $thisCount) {
            // delete some more
            $startTime = time();
            $results = $deleteStmt->execute();
            $deleteQueryTime = sprintf('%d', time() - $startTime);

            // update counters
            $lastCount = $thisCount;
            $startTime = time();
            $thisCount = $countQuery->getSingleScalarResult();
            $countQueryTime = sprintf('%d', time() - $startTime);

            $output->writeln((string) $results . ' ' . $lastCount . ' ' . $thisCount
                . ' ' . $deleteQueryTime . '/' . $countQueryTime);
        }
    }

//    protected function execute(InputInterface $input, OutputInterface $output)
//    {
//        $output->writeln("Deleting user log");
//
//        $queryBuilder = $this->getServiceManager()->get('userLogRepository')->createQueryBuilder('e');
//        $queryBuilder->select($queryBuilder->expr()->count('e.id'));
//
//        // enrollment 64464
//        // user log 15160985
//        $deleteSql = '
//            DELETE FROM user_log
//            WHERE id < 15160985
//            ORDER BY id asc
//            LIMIT 10000
//        ';
//
//        //$countQuery = $queryBuilder->getQuery();
//        $deleteStmt = $this->getServiceManager()->get('entityManager')->getConnection()->prepare($deleteSql);
//
//        //$lastCount = 0;
//        //$thisCount = $countQuery->getSingleScalarResult();
//
//        $counter = 500;
//        while ($counter > 0) {
//            // delete some more
//            $startTime = time();
//            $results = $deleteStmt->execute();
//            $deleteQueryTime = sprintf('%d', time() - $startTime);
//
//            // update counters
//            //$lastCount = $thisCount;
//            //$startTime = time();
//            //$thisCount = $countQuery->getSingleScalarResult();
//            $counter--;
//            $countQueryTime = sprintf('%d', time() - $startTime);
//
//            $output->writeln((string) $results . ' '
//                . ' ' . $deleteQueryTime . '/' . $countQueryTime . ' ' . $counter);
//        }
//    }
}