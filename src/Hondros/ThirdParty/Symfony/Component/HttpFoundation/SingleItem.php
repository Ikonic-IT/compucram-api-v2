<?php

namespace Hondros\ThirdParty\Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;

class SingleItem
{
    protected $httpRequest;
    protected $kind;
    
    public function __construct($httpRequest = null, $kind = '')
    {
        if (is_null($httpRequest)) {
            $httpRequest = Request::createFromGlobals();
        }
        
        $this->httpRequest = $httpRequest;
        $this->kind = $kind;
    }
    
    public function format($data)
    {
        return [
            //'kind' => $this->kind,
            //'selfLink' => $this->httpRequest->getUri(),
            'item' => $data
            // add debug but need config for that
        ];
    }
}
