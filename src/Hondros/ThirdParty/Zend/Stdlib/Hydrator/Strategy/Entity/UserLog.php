<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class UserLog extends EntityAbstract
{
    public function __construct($includes = [])
    {
        $this->hydrator = new Hydrator\ClassMethods(false);

        if (in_array('user', $includes)) {
            $this->hydrator->addStrategy('user', new Hydrator\Strategy\Entity\User());
        }

        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
    }
}