<?php

namespace Hondros\Common;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ArrayIterator;

class DoctrineCollection extends ArrayIterator
{
    protected $iterator;
    protected $offset = 0;
    protected $limit = 0;
    protected $total = 0;
    
    public function setOffset($offset)
    {
        $this->offset = $offset;
        
        return $this;
    }
    
    public function setLimit($limit)
    {
        $this->limit = $limit;
    
        return $this;
    }
    
    public function setTotal($total)
    {
        $this->total = $total;
    
        return $this;
    }
    
    public function __construct($collection, $strategyName = null, $includes = []) 
    {
        if ($collection instanceof Paginator) {
            $this->offset = $collection->getQuery()->getFirstResult() ?: 0;
            $this->limit = $collection->getQuery()->getMaxResults() ?: 0;
            $this->total = count($collection);
        }

        // need to extract data
        if (!is_null($strategyName)) {
            $className = "Hondros\\ThirdParty\\Zend\\Stdlib\\Hydrator\\Strategy\\Entity\\{$strategyName}";
            $strategy = new $className($includes);
            $hydrator = $strategy->getHydrator();
        } else {
            $hydrator = new Hydrator\ClassMethods();
        }
        
        $data = [];
        foreach ($collection as $entity) {
            $data[] = (new \Hondros\Common\Doctrine())->cleanUp($hydrator->extract($entity));
        }
            
        // init main class
        parent::__construct($data);
    }
    
    public function getPagination()
    {
        $o = new \stdClass();
        $o->offset = $this->offset;
        $o->limit = $this->limit;
        $o->total = $this->total;
        $o->page = 1;
        $o->pages = null;
        $o->prev = null;
        $o->next = null;
        
        if ($o->limit == 0) {
            return $o;
        }
        
        $o->page = $this->total ? (int)floor($o->offset / $o->limit) + 1 : 1;
        $o->pages = $this->total ? (int)ceil($o->total / $o->limit) : 1;
        $o->prev = $o->page > 1 ? $o->page - 1 : null;
        
        // if that a valid prev page?
        if ($o->prev > $o->pages) {
            $o->prev = null;
        }
        
        $o->next = $o->page < $o->pages ? $o->page + 1 : null;
        
        // if that a valid prev page?
        if ($o->next > $o->pages) {
            $o->next = null;
        }
        
        $o->start = $o->offset + 1;
        
        if (($o->offset + $o->limit) > $o->total) {
            $o->end = $o->total;
        } else {
            $o->end = $o->limit * $o->page;
        }

        return $o;
    }
}
