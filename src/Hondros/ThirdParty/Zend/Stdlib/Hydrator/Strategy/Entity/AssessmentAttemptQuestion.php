<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class AssessmentAttemptQuestion extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('assessmentAttempt', $includes)) {
            $this->hydrator->addStrategy('assessmentAttempt', new Hydrator\Strategy\Entity\AssessmentAttempt());
        }
        
        if (in_array('module', $includes)) {
            $this->hydrator->addStrategy('module', new Hydrator\Strategy\Entity\Module());
        }
        
        if (in_array('question', $includes)) {
            $questionIncludes = [];
            if (in_array('question.answers', $includes)) {
                $questionIncludes = ['answers'];
            }
        
            $this->hydrator->addStrategy('question', new Hydrator\Strategy\Entity\Question($questionIncludes));
        }
        
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}