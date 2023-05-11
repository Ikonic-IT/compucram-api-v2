<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Organization extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('parent', $includes)) {
            $this->hydrator->addStrategy('parent', new Hydrator\Strategy\Entity\Organization());
        }
        
        if (in_array('children', $includes)) {
            $this->hydrator->addStrategy('children', new Hydrator\Strategy\Entity\Organization());
        }
        
        if (in_array('template', $includes)) {
            $this->hydrator->addStrategy('template', new Hydrator\Strategy\Entity\Template());
        }
        
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}