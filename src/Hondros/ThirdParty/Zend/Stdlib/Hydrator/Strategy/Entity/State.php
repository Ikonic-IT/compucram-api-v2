<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;

class State extends EntityAbstract
{
    public function __construct()
    {
        $this->hydrator = new Hydrator\ClassMethods(false);
    }
}