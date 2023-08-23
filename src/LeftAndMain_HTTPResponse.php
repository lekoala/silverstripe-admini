<?php

namespace LeKoala\Admini;

use SilverStripe\Control\HTTPResponse;

/**
 * Allow overriding finished state for faux redirects.
 */
class LeftAndMain_HTTPResponse extends HTTPResponse
{

    protected $isFinished = false;

    public function isFinished()
    {
        return (parent::isFinished() || $this->isFinished);
    }

    public function setIsFinished($bool)
    {
        $this->isFinished = $bool;
    }

    public static function cloneFrom(HTTPResponse $response): LeftAndMain_HTTPResponse
    {
        $newResponse = new LeftAndMain_HTTPResponse(
            $response->getBody(),
            $response->getStatusCode(),
            $response->getStatusDescription()
        );
        foreach ($response->getHeaders() as $k => $v) {
            $newResponse->addHeader($k, $v);
        }
        foreach ($response->getHeaders() as $k => $v) {
            $newResponse->addHeader($k, $v);
        }
        return $newResponse;
    }
}
