<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InvalidCacheParamException extends Exception
{
    protected ?array $payloadLeadNote = null;

    public function __construct($message = "", $code = 0, Throwable $previous = null, ?array $payloadLeadNote = null)
    {
        parent::__construct($message, $code, $previous);
        $this->payloadLeadNote = $payloadLeadNote;
    }

    /**
     * @return array|null
     */
    public function getPayloadLeadNote(): ?array
    {
        return $this->payloadLeadNote;
    }
}
