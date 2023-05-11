<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 12/5/2015
 * Time: 8:59 PM
 */

namespace Hondros\Api\Console\Job\RunOnce;

use Knp\Command\Command;
use Laminas\Config\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateQuestionBankToModuleQuestion extends Command
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
        $this->setName("runOnce:migrateQuestionBankToModuleQuestion")
            ->setDescription("Copy data from question bank to module question.")
            ->addOption("disableHidden", "d", InputOption::VALUE_NONE,
                "Disable all questions in hidden question bank. Run first, can run multiple times.")
            ->addOption("migrateQuestions", 'm', InputOption::VALUE_NONE,
                "Migrate all question bank to module question. Run second, can only run once.")
            ->addOption("removeDuplicates", 'r', InputOption::VALUE_NONE,
                "Deletes all duplicate questions in progress as well. Run last, can run multiple times.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $disableHidden = $input->getOption('disableHidden');
        $migrateQuestions = $input->getOption('migrateQuestions');
        $removeDuplicates = $input->getOption('removeDuplicates');

        if (!$disableHidden && !$migrateQuestions && !$removeDuplicates) {
            $output->writeln("An option is required to run this command.");
            exit(1);
        }

        // disable listeners for performance
        $config = $this->serviceManager->get('config');

        $newConfig = new Config([
            'doctrine' => [
                'listeners' => [
                    'moduleQuestionListener' => false,
                    'questionListener' => false,
                    'answerListener' => false
                ]
            ]
        ]);

        $config->merge($newConfig);

        $manager = new MigrateQuestionBankToModuleQuestionManager($this->getServiceManager());

        // should run first, can run multiple times
        if ($disableHidden) {
            $output->writeln("Disabling questions.");
            $response = $manager->disableHiddenQuestions();
            $output->writeln("Disabled {$response['disabled']}.");
        }

        // runs second, can only run once. this populates all the correct questions for the different types
        if ($migrateQuestions) {
            $output->writeln("Migrating questions.");
            $response = $manager->migrateQuestions();
            $output->writeln("Migrated {$response['migrated']}.");
        }

        // run last, can run multiple times. After all buckets are configured right, now we can remove dups
        if ($removeDuplicates) {
            $output->writeln("Removing duplicates.");
            $response = $manager->removeDuplicateQuestions($output);
            $matched = count($response['matching']);
            $output->writeln("Matched {$matched}.");
            $output->writeln("Replaced {$response['replacedCount']}.");
            $output->writeln("Deleted within module {$response['deletedWithinCount']}.");
            $output->writeln("Deleted {$response['deletedCount']}.");
        }

        $output->writeln("All done.");
    }
}