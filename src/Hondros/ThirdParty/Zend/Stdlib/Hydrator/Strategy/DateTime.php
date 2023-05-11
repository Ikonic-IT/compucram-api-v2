<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy;

use Laminas\Stdlib\Hydrator\Strategy\StrategyInterface;

class DateTime implements StrategyInterface
{
    
    public function __construct()
    {

    }
    
    public function extract($dateTime)
    {
        if (empty($dateTime)) {
            return null;
        }
        
        return $dateTime->getTimestamp();
    }

    public function hydrate($timestamp)
    {
        if (empty($timestamp)) {
            return null;
        }
        
        $date = new \DateTime();
        return $date->setTimestamp($timestamp);
    }
}