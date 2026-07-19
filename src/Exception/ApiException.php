<?php

declare(strict_types=1);

namespace Glytos\Exception;

/**
 * Thrown on any non-2xx API response (and on transport failures, with status 0).
 *
 * The API's machine-readable error code is exposed as {@see $errorCode} rather than
 * the inherited integer {@see getCode()}, which stays the HTTP status.
 */
final class ApiException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?string $requestId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
