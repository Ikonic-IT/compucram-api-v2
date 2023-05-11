<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 1/24/2015
 * Time: 10:16 PM
 */

namespace Hondros\Api\Console\Job\Content;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Config\Config;

class ImportModule extends Command
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceManager $serviceManager
     * @return ImportModule
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return \Hondros\Api\Util\ContentImporter
     */
    public function getContentImporter()
    {
        return $this->getServiceManager()->get('contentImporter');
    }

    protected function configure()
    {
        $this->setName("content:importModules")
            ->setDescription("Imports new modules into the DB");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Importing Modules");

        // disable listeners for performance, we'll do some bulk processing after
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

        $results = $this->getContentImporter()->importFiles('modules');
        var_dump($results);

        // @todo need some bulk process to add all new questions to cache
        if (empty($results['errors'])) {

        }
    }
}