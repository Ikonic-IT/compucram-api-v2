<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator;

use Laminas\Hydrator\ClassMethodsHydrator as ZendClassMethods;

class ClassMethods extends ZendClassMethods
{
    /**
     * For doctrine, if we have an object with multiple relationships, only loaded
     * relationship objects will be extracted. Any proxy object will be ignored.
     * Easily configurable by the doctrine queries or annotations with eager loaded
     * 
     * @param object $object
     * @return array
     */
    public function extract(object $object): array
    {
        
        // track the hash of each object so we don't try and load it twice
        // to prevent a loop
        //$hash = spl_object_hash($object);
        $data = [];
        // doctrine has proxies when the entity is not loaded, we can ignore those
        if ((method_exists($object, '__isInitialized') && $object->__isInitialized() == false)
            || get_class($object) == 'Doctrine\ORM\PersistentCollection' && !$object->isInitialized()) {
            return $data;
        }

        if ($object instanceof \Doctrine\Common\Collections\ArrayCollection) {
            foreach ($object as $item) {
                $data[] = parent::extract($item);
            }
            return $data;
        }

        // let zend do it's thing
        return parent::extract($object);
    }
}