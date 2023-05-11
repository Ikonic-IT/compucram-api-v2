<?php

namespace Hondros\ThirdParty\Symfony\Component\HttpFoundation;

use Hondros\Common\Collection;
use Hondros\Common\DoctrineCollection;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class JsonResponse extends SymfonyJsonResponse
{
    /**
     * {@inheritdoc}
     */
    public static function create($data = null, $status = 200, $headers = array())
    {
        // massage the data
        if ($data instanceof DoctrineCollection || $data instanceof Collection) {
            $type = new MultipleItems(null, 'users');
            return new static($type->format($data), $status, $headers);
        }

        $type = new SingleItem(null, 'users');
        return new static($type->format($data), $status, $headers);
    }
}
