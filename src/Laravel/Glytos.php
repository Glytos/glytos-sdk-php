<?php

declare(strict_types=1);

namespace Glytos\Laravel;

use Glytos\Client;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the container-bound {@see Client}. Proxies method calls such as
 * `Glytos::request('GET', '/workflows')`.
 *
 * For the resource namespaces, inject {@see Client} and use property access
 * (`$glytos->workflows->list()`), since facades proxy methods, not properties.
 *
 * @method static array<mixed>|string|null request(string $method, string $path, ?array $body = null, ?array $query = null)
 *
 * @see Client
 */
final class Glytos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
