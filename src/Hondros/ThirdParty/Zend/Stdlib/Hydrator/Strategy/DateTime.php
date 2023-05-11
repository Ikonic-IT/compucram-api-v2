<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy;

use Laminas\Hydrator\Strategy\StrategyInterface;

class DateTime implements StrategyInterface
{
    
    public function __construct()
    {

    }
    
    public function extract($dateTime, ?object $object = null)
    {
        if (empty($dateTime)) {
            return null;
        }
        
        return $dateTime->getTimestamp();
    }

    public function hydrate($timestamp, ?array $data = null)
    {
        if (empty($timestamp)) {
            return null;
        }
        
        $date = new \DateTime();
        return $date->setTimestamp($timestamp);
    }
}