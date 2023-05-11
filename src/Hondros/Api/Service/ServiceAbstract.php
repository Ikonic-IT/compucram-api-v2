<?php

namespace Hondros\Api\Service;

use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;

abstract class ServiceAbstract
{
    /**
     * @var \Hondros\Api\Model\Repository\RepositoryAbstract
     */
    protected $repository;

    public function __call($name, $params)
    {
        if (substr($name, 0, 7) == 'findFor') {
            $object = substr($name, 7);
            $object[0] = strtolower($object[0]);
            return $this->findFor($object, $params[0], $params[1]);
        }
        
        throw new \BadMethodCallException("Unknown method {$name}", 500);
    }

    /**
     * Finds object from id
     *
     * can pass in includes to load relationship objects
     *
     * @param int $id
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function find($id, $params = [])
    {
        // validate id
        if (!is_numeric($id)) {
            throw new InvalidArgumentException("Invalid id value of {$id}");
        }
    
        // any includes?
        $includes = !empty($params['includes']) ? $params['includes'] : [];
        
        $entity = $this->repository->findOverride($id, $includes);
        
        return new DoctrineSingle($entity, static::ENTITY_STRATEGY, $includes);
    }

    // @todo validation
    public function findAll($params)
    {
        $filters = !empty($params['filters']) ? $params['filters'] : [];
        $includes = !empty($params['includes']) ? $params['includes'] : [];
        $orderBy = !empty($params['orderBy']) ? $params['orderBy'] : [];
        $page = !empty($params['page']) ? $params['page'] : 1;
        $pageSize = !empty($params['pageSize']) ? $params['pageSize'] : 50;
        $offset = ($page - 1) * $pageSize;

        $paginator = $this->repository->findAllOverride($filters, $includes, $orderBy, $pageSize, $offset);

        return new DoctrineCollection($paginator, static::ENTITY_STRATEGY, $includes);
    }
    
    /**
     * used to pull back an entity collection by the id of a relationship object
     * 
     * @param string $object
     * @param int $id
     * @param array $params
     * @return \Hondros\Common\DoctrineCollection
     */
    public function findFor($object, $id, $params)
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("The following id {$id} is not valid.", 400);
        }
        
        $includes = !empty($params['includes']) ? $params['includes'] : [];
        $paginator = $this->repository->findFor($object, $id, $params);
        
        return new DoctrineCollection($paginator, static::ENTITY_STRATEGY, $includes);
    }
}