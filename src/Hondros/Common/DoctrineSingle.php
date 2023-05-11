<?php

namespace Hondros\Common;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;
use ArrayIterator;

class DoctrineSingle extends ArrayIterator
{
    public function __construct($entity, $strategyName = null, $includes = []) 
    {
        // need to extract data
        if (!is_null($strategyName)) {
            $className = "Hondros\\ThirdParty\\Zend\\Stdlib\\Hydrator\\Strategy\\Entity\\{$strategyName}";
            $strategy = new $className($includes);
            $hydrator = $strategy->getHydrator();
        } else {
            $hydrator = new Hydrator\ClassMethods();
        }
        
        // get array then clean up
        $data = (new \Hondros\Common\Doctrine())->cleanUp($hydrator->extract($entity));
        
        // init main class
        parent::__construct($data);
    }
}
