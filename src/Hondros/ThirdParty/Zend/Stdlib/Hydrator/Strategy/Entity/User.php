<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class User extends EntityAbstract
{
    public function __construct()
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
        $this->hydrator->addStrategy('created', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('modified', new Hydrator\Strategy\DateTime());
        $this->hydrator->addStrategy('lastLogin', new Hydrator\Strategy\DateTime());
    }
}