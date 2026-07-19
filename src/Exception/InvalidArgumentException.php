<?php

declare(strict_types=1);

namespace Glytos\Exception;

/**
 * Thrown for invalid client-side arguments (e.g. a missing API key), before any
 * request is made.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
