<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Question extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('questionBank', $includes)) {
            $this->hydrator->addStrategy('questionBank', new Hydrator\Strategy\Entity\QuestionBank());
        }
        
        if (in_array('answers', $includes)) {
            $this->hydrator->addStrategy('answers', new Hydrator\Strategy\Entity\Answer());
        }

        if (in_array('createdBy', $includes)) {
            $this->hydrator->addStrategy('user', new Hydrator\Strategy\Entity\User());
        }

        if (in_array('modifiedBy', $includes)) {
            $this->hydrator->addStrategy('user', new Hydrator\Strategy\Entity\User());
        }
        
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}