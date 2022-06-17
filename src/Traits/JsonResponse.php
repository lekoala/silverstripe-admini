<?php

namespace LeKoala\Admini\Traits;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;

trait JsonResponse
{
    /**
     * Return an error HTTPResponse encoded as json
     *
     * @param int $errorCode
     * @param string $errorMessage
     * @throws HTTPResponse_Exception
     */
    public function jsonError($errorCode, $errorMessage = null)
    {
        $request = $this->getRequest();

        // Build error from message
        $error = [
            'type' => 'error',
            'code' => $errorCode,
        ];
        if ($errorMessage) {
            $error['value'] = $errorMessage;
        }

        // Support explicit error handling with status = error, or generic message handling
        // with a message of type = error
        $result = [
            'status' => 'error',
            'errors' => [$error]
        ];
        $response = HTTPResponse::create(json_encode($result), $errorCode)
            ->addHeader('Content-Type', 'application/json');

        // Call a handler method such as onBeforeHTTPError404
        $this->extend("onBeforeJSONError{$errorCode}", $request, $response);

        // Call a handler method such as onBeforeHTTPError, passing 404 as the first arg
        $this->extend('onBeforeJSONError', $errorCode, $request, $response);

        // Throw a new exception
        throw new HTTPResponse_Exception($response);
    }

    /**
     * @param array $data
     * @return HTTPResponse
     */
    public function jsonResponse($data)
    {
        $result = [
            'status' => 'success',
            'data' => $data
        ];
        $response = HTTPResponse::create(json_encode($result))
            ->addHeader('Content-Type', 'application/json');

        return $response;
    }
}
