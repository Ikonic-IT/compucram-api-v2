<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class ProgressQuestion extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        
        if (in_array('progress', $includes)) {
            $this->hydrator->addStrategy('progress', new Hydrator\Strategy\Entity\Progress());
        }
        
        if (in_array('question', $includes)) {
            $questionIncludes = [];
            if (in_array('question.answers', $includes)) {
                $questionIncludes = ['answers'];
            }
            
            $this->hydrator->addStrategy('question', new Hydrator\Strategy\Entity\Question($questionIncludes));
        }
    }
}