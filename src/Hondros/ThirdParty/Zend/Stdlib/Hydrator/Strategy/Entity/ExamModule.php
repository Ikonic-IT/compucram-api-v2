<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class ExamModule extends EntityAbstract
{   
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('exam', $includes)) {
            $this->hydrator->addStrategy('exam', new Hydrator\Strategy\Entity\Exam());
        }
        
        if (in_array('module', $includes)) {
            $this->hydrator->addStrategy('module', new Hydrator\Strategy\Entity\Module());
        }
        
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}