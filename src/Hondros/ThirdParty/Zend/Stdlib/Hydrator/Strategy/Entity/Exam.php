<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Exam extends EntityAbstract
{   
    public function __construct()
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        $this->hydrator->addStrategy('industry', new Hydrator\Strategy\Entity\Industry());
        $this->hydrator->addStrategy('modules', new Hydrator\Strategy\Entity\Module());
        $this->hydrator->addStrategy('state', new Hydrator\Strategy\Entity\State());
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}