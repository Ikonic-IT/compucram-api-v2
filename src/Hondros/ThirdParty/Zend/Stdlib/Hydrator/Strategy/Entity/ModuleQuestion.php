<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class ModuleQuestion extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('module', $includes)) {
            $this->hydrator->addStrategy('module', new Hydrator\Strategy\Entity\Module());
        }
        
        if (in_array('question', $includes)) {
            $this->hydrator->addStrategy('question', new Hydrator\Strategy\Entity\Question());
        }
    }
}