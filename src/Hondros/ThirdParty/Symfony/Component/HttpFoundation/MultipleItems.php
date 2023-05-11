<?php

namespace Hondros\ThirdParty\Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;

class MultipleItems
{
    protected $httpRequest;
    protected $kind;
    
    public function __construct($httpRequest = null, $kind)
    {
        if (is_null($httpRequest)) {
            $httpRequest = Request::createFromGlobals();
        }
        
        $this->httpRequest = $httpRequest;
        //$this->kind = $kind;
    }
    
    public function format($collection)
    {
        $pagination = $collection->getPagination();
        $previousLink = null;
        $nextLink = null;
        
        // setup page links
        if (!is_null($pagination->prev)) {
            if (false === strpos($this->httpRequest->getUri(), "page={$pagination->page}")) {
                $previousLink = $this->httpRequest->getUri();
                $previousLink .= is_null($this->httpRequest->getQueryString()) ? "?" : "&";
                $previousLink .= "page={$pagination->prev}";
            } else {
                $previousLink = str_replace("page={$pagination->page}", "page={$pagination->prev}", $this->httpRequest->getUri());
            }
        }
        
        // setup page links
        if (!is_null($pagination->next)) {
            if (false === strpos($this->httpRequest->getUri(), "page={$pagination->page}")) {
                $nextLink = $this->httpRequest->getUri();
                $nextLink .= is_null($this->httpRequest->getQueryString()) ? "?" : "&";
                $nextLink .= "page={$pagination->next}";
            } else {
                $nextLink = str_replace("page={$pagination->page}", "page={$pagination->next}", $this->httpRequest->getUri());
            }
        }
        
        return [
            //'kind' => $this->kind,
            //'selfLink' => $this->httpRequest->getUri(),
            //'previousLink' => $previousLink,
            //'nextLink' => $nextLink,
            'page' => $pagination->page,
            'pages' => (int) $pagination->pages,
            'itemsPerPage' => (int) $pagination->limit,
            'total' => (int) $pagination->total,
            'itemsCount' => (int) count($collection),
            'items' => (array)$collection
            // add debug but need config for that
        ];
    }
}
