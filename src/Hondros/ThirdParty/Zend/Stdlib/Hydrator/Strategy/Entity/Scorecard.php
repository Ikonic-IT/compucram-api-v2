<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class Scorecard extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('examCategoryScores', $includes)) {
            $this->hydrator->addStrategy('examCategoryScores', new Hydrator\Strategy\Entity\ExamCategoryScore());
        }

        if (in_array('simulatedExamScores', $includes)) {
            $this->hydrator->addStrategy('simulatedExamScores', new Hydrator\Strategy\Entity\SimulatedExamScore());
        }

    }
}