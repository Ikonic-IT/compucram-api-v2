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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateModule extends Command
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
     * @return UpdateModule
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
        $this->setName("content:updateModules")
            ->setDescription("Update modules based on content edits");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Updating Modules");
        $results = $this->getContentImporter()->updateModules();
        var_dump($results);
    }
}