<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class QuestionAudit extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);

        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
    }
}