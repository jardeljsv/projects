<?php

namespace GlpiPlugin\G4freports\Api;

class GlpiApiException extends \RuntimeException
{
    private int $httpStatus;
    private $responseBody;

    public function __construct(string $message, int $httpStatus = 0, $responseBody = null)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
