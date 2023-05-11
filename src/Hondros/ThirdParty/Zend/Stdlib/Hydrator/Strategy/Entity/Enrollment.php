<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Enrollment extends EntityAbstract
{   
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('started', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('expiration', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('converted', new Hydrator\Strategy\DateTime());
        
        if (in_array('user', $includes)) {
            $this->hydrator->addStrategy('user', new Hydrator\Strategy\Entity\User());
        }
        
        if (in_array('exam', $includes)) {
            $this->hydrator->addStrategy('exam', new Hydrator\Strategy\Entity\Exam());
        }
        
        if (in_array('organization', $includes)) {
            $this->hydrator->addStrategy('organization', new Hydrator\Strategy\Entity\Organization());
        }
        
        if (in_array('progresses', $includes)) {
            $this->hydrator->addStrategy('progresses', new Hydrator\Strategy\Entity\Progress());
        }
    }
}