<?php

declare(strict_types=1);

namespace Modules\V1\AI\Exceptions;

use Exception;
use Throwable;

final class InvalidResponseException extends Exception
{
    protected mixed $response;

    public function __construct(
        string $message = "Invalid response from AI service",
        mixed $response = null,
        int $code = 422,
        ?Throwable $previous = null
    ) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): mixed
    {
        return $this->response;
    }
}
