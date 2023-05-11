<?php

namespace Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity;

use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;

/**
 * Class EntityAbstract
 * @package Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity
 * @todo this is weak, need to re-do this part. It's the hardest part of the application to work with
 */
abstract class EntityAbstract implements StrategyInterface
{
    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @param mixed $properties
     * @return array|null
     */
    public function extract($properties, ?object $object = null)
    {
        if (empty($properties) || get_class($properties) == 'Doctrine\ORM\PersistentCollection' && !$properties->isInitialized()) {
            return null;
        }

        if (get_class($properties) == 'Doctrine\ORM\PersistentCollection') {
            $data = [];
            foreach ($properties->toArray() as $key => $value) {
                $data[] = $this->hydrator->extract($value);
            }

            return $data;
        }

        return $this->hydrator->extract($properties);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function hydrate($value, ?array $data = null)
    {
//         $name = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1);
//         $entityName = "\\Hondros\\Api\\Model\\Entity\\{$name}";

//         return $this->hydrator->hydrate($data);

        return $value;
    }

    /**
     * @return HydratorInterface
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }
}