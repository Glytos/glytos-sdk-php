<?php

declare(strict_types=1);

namespace Glytos\Resource;

use Glytos\Client;

/**
 * Base for every resource namespace: holds the client each method dispatches through.
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Client $client)
    {
    }
}
