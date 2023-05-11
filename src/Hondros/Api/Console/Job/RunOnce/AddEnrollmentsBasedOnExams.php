<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 05/26/2018
 */

namespace Hondros\Api\Console\Job\RunOnce;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddEnrollmentsBasedOnExams extends Command
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @return \Laminas\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return MigrateQuestionBankToModuleQuestion
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
        
        return $this;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
    }

    protected function configure()
    {
        $this->setName("runOnce:addEnrollmentsBasedOnExams")
            ->setDescription("Add new enrollments for users with specific existing exams.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new AddEnrollmentsBasedOnExamsManager(
            $this->getServiceManager()->get('examRepository'),
            $this->getServiceManager()->get('enrollmentRepository'),
            $this->getServiceManager()->get('enrollmentService'),
            $this->getEntityManager()
            );

        $results = $manager->addEnrollments();

        $output->writeln("Users found: {$results['users']}.");
        $output->writeln("Enrollments Added: {$results['enrollmentsCreated']}.");
        $output->writeln("Mapped Exams: " . json_encode($results['mappedExams']));
        $output->writeln("All done.");
    }
}