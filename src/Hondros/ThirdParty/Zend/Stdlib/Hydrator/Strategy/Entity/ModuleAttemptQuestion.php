<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class ModuleAttemptQuestion extends EntityAbstract
{
    public function __construct()
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        $this->hydrator->addStrategy('moduleAttempt', new Hydrator\Strategy\Entity\ModuleAttempt());
        $this->hydrator->addStrategy('question', new Hydrator\Strategy\Entity\Question());
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}