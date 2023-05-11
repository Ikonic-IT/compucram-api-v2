<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Module extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('examModule', $includes)) {
            $this->hydrator->addStrategy('examModule', new Hydrator\Strategy\Entity\ExamModule());
        }
        
        if (in_array('industry', $includes)) {
            $this->hydrator->addStrategy('industry', new Hydrator\Strategy\Entity\Industry());
        }
        
        if (in_array('state', $includes)) {
            $this->hydrator->addStrategy('state', new Hydrator\Strategy\Entity\State());
        }
        
        if (in_array('preassessmentBank', $includes)) {
            $this->hydrator->addStrategy('preassessmentBank', new Hydrator\Strategy\Entity\QuestionBank());
        }
        
        if (in_array('studyBank', $includes)) {
            $this->hydrator->addStrategy('studyBank', new Hydrator\Strategy\Entity\QuestionBank());
        }
        
        if (in_array('practiceBank', $includes)) {
            $this->hydrator->addStrategy('practiceBank', new Hydrator\Strategy\Entity\QuestionBank());
        }
        
        if (in_array('examBank', $includes)) {
            $this->hydrator->addStrategy('examBank', new Hydrator\Strategy\Entity\QuestionBank());
        }
        
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}