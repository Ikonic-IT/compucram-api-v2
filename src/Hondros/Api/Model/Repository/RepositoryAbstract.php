<?php

namespace Hondros\Api\Model\Repository;

use Hondros\Common\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Hondros\ThirdParty\Zend\Stdlib\Hydrator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use \InvalidArgumentException;
use Predis\Client as Redis;

abstract class RepositoryAbstract extends EntityRepository
{
    const RESULTS_LIMIT = 50;
    const CACHE_ENABLED = false;

    /**
     * @return Redis
     */
    public function getCacheAdapter() 
    {
        return $this->redis;
    }

    /**
     * Regular find but also allows joins
     * @param $id
     * @param array $includes
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function findOverride($id, $includes = [])
    {
        $entities = ['e'];
        $qb = $this->createQueryBuilder('e');

        foreach ($includes as $include) {
            $entities[] = $include;
            $qb->leftJoin("e.{$include}", $include);
        }

        $qb->select($entities)->where("e.id = {$id}");
        $entity = $qb->getQuery()->getOneOrNullResult();

        if (empty($entity)) {
            throw new \Exception("Id not found", 404);
        }

        return $entity;
    }

    // @todo limit 'limit' if greater than x
    // @todo refactor and reuse code between this and findFor
    public function findAllOverride(array $filters = null, array $includes = null, array $orderBy = [], $limit = 50, $offset = 0)
    {
        $alias = "e";
        $entities = [$alias];

        $subIncludes = false;

        $qb = $this->createQueryBuilder($alias);

        // handle includes and sub includes so we can go two levels deep
        foreach ($includes as $include) {
            $subIncludes = true;
            // check if it's a sub include
            $pos = strpos($include, '.');

            if ($pos === false) {
                $entities[] = $include;
                $qb->leftJoin("e.{$include}", $include);
            } else {
                $name = substr($include, $pos + 1);
                $entities[] = $name;
                $qb->leftJoin($include, $name);
            }
        }

        // order by
        foreach ($orderBy as $order) {
            $property = !empty($order['property']) ? $alias . "." . trim($order['property']) : null;
            $value = isset($order['value']) ? strtolower(trim($order['value'])) : 'asc';

            // validate
            if (is_null($property)) {
                continue;
            }

            switch ($value) {
                case 'desc':
                    $qb->addOrderBy($property, $value);
                    break;
                default: // asc
                    $qb->addOrderBy($property, 'asc');
            }
        }

        // filters
        $filterCounter = 0;
        foreach ($filters as $filter) {
            $property = !empty($filter['property']) ? $alias . "." . trim($filter['property']) : null;
            $value = isset($filter['value']) ? trim($filter['value']) : null;
            $condition = !empty($filter['condition']) ? strtolower(trim($filter['condition'])) : '=';

            // validate
            if (is_null($property)) {
                continue;
            }

            // conditions http://doctrine-orm.readthedocs.org/en/latest/reference/query-builder.html
            $bindName = $filter['property'] . $filterCounter++;
            switch ($condition) {
                case 'ne':
                    $qb->andWhere($qb->expr()->neq($property, ":{$bindName}")); // without alias
                    $qb->setParameter($bindName, $value);
                    break;
                case 'gt':
                    $qb->andWhere($qb->expr()->gt($property, ":{$bindName}"));
                    $qb->setParameter($bindName, $value);
                    break;
                case 'lt':
                    $qb->andWhere($qb->expr()->lt($property, ":{$bindName}"));
                    $qb->setParameter($bindName, $value);
                    break;
                case 'like':
                    $qb->andWhere($qb->expr()->like($property, $qb->expr()->literal("%{$value}%")));
                    break;
                case 'sw':
                    $qb->andWhere($qb->expr()->like($property, $qb->expr()->literal("{$value}%")));
                    break;
                case 'ew':
                    $qb->andWhere($qb->expr()->like($property, $qb->expr()->literal("%{$value}")));
                    break;
                default: // eq
                    $qb->andWhere($qb->expr()->eq($property, ":{$bindName}"));
                    $qb->setParameter($bindName, $value);
            }
        }

        // set max and pagination
        $qb->select($entities)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), $subIncludes);
    }

    // @todo don't take in params, take in the actual variables needed like findAllOverride
    public function findFor($object, $id, $params = [])
    {
        $entities = ['e'];
        $qb = $this->createQueryBuilder('e');
    
        // any includes?
        $includes = !empty($params['includes']) ? $params['includes'] : [];
        $subIncludes = false;
    
        // handle includes and sub includes so we can go two levels deep
        foreach ($includes as $include) {
            $subIncludes = true;
            // check if it's a sub include
            $pos = strpos($include, '.');
    
            if ($pos === false) {
                $entities[] = $include;
                $qb->leftJoin("e.{$include}", $include);
            } else {
                $name = substr($include, $pos + 1);
                $entities[] = $name;
                $qb->leftJoin($include, $name);
            }
        }
    
        $qb->select($entities);
        $qb->where("e.{$object} = {$id}");
        $query = $qb->getQuery();
    
        if (!empty($params['page']) || !empty($params['pageSize'])) {
            $page = !empty($params['page']) ? (int) $params['page'] : 1;
            $offset = 0;
            $limit = !empty($params['pageSize']) ? (int)$params['pageSize'] : 50;
            
            // update offset
            $offset = ($page - 1) * $limit;

            $query->setFirstResult($offset)->setMaxResults($limit);
        }
    
        // cache?
        // @todo remove this from here, it need to be implemented by each object as it needs it. Used by user... maybe?
        $class = get_called_class(); 
        if ($class::CACHE_ENABLED) {
            $cacheId = $class::CACHE_ID . ':findFor:' . $object . ':' . $id . ':' . md5(serialize($params));
            $query->useResultCache($class::CACHE_ENABLED, $class::CACHE_TTL, $cacheId);
        }
    
        return new Paginator($query, $subIncludes);
    }
}