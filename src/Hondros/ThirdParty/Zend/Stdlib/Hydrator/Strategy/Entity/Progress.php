<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Progress extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
		$this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
		$this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
        
        if (in_array('module', $includes)) {
            $this->hydrator->addStrategy('module', new Hydrator\Strategy\Entity\Module());
        }
        
        if (in_array('enrollment', $includes)) {
            $this->hydrator->addStrategy('enrollment', new Hydrator\Strategy\Entity\Enrollment());
        }
        
        if (in_array('questions', $includes)) {
            $this->hydrator->addStrategy('questions', new Hydrator\Strategy\Entity\Question());
        }
    }
}