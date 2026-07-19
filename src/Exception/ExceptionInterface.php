<?php

declare(strict_types=1);

namespace Glytos\Exception;

/**
 * Marker interface implemented by every exception the SDK throws, so callers can
 * catch all Glytos failures with a single `catch (\Glytos\Exception\ExceptionInterface $e)`.
 */
interface ExceptionInterface extends \Throwable
{
}
