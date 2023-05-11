<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class ModuleAttempt extends EntityAbstract
{
    public function __construct()
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        $this->hydrator->addStrategy('enrollment', new Hydrator\Strategy\Entity\Enrollment());
        $this->hydrator->addStrategy('module', new Hydrator\Strategy\Entity\Module());
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('completed', new Hydrator\Strategy\DateTime());
    }
}