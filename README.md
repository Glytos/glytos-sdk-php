# glytos/glytos

[![CI](https://github.com/Glytos/glytos-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Glytos/glytos-sdk-php/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/glytos/glytos)](https://packagist.org/packages/glytos/glytos)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

The official [Glytos](https://glytos.com) server SDK for PHP.

Call the Glytos API from your backend with an API key: build and run voice agents,
start phone calls, mint browser web-call tokens, manage phone numbers, and verify
webhooks.

> Never ship an API key to the browser. For in-browser voice, use the `@glytos/web`
> package with a short-lived token you mint here.

## Requirements

- PHP 8.1+
- Any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client. One is discovered
  automatically when installed (Guzzle, Symfony HTTP Client, ...); you can also inject
  your own. Most frameworks already ship one.

## Install

```bash
composer require glytos/glytos
```

If your project has no HTTP client yet, add one (discovered automatically):

```bash
composer require guzzlehttp/guzzle
```

## Quickstart

```php
use Glytos\Client;

$glytos = new Client('gly_...');

// List your agents
$agents = $glytos->workflows->list();

// Mint a web-call token for the browser
$token = $glytos->calls->webToken(workflowUuid: $agents[0]['uuid']);
echo $token['token'], ' ', $token['ws_url'];
```

Scope the client to an environment, a regional stack, or your own HTTP client via the
constructor (named arguments keep it readable):

```php
$glytos = new Client('gly_...', environment: 'prod');

$overview = $glytos->request('GET', '/analytics/overview');
```

## Resources

| Namespace | Methods |
| --- | --- |
| `$glytos->workflows` | `list`, `retrieve`, `create`, `publish`, `delete`, `templates`, `session`, `sessionEvents` |
| `$glytos->calls` | `create`, `list`, `retrieve`, `webToken`, `control` |
| `$glytos->phoneNumbers` | `search`, `list`, `provision`, `assign`, `release` |
| `$glytos->sessions` | `list` |
| `$glytos->webhooks` | `list`, `create`, `delete`, `events`, `verify` |

Any endpoint without a dedicated helper is one call away with
`$glytos->request($method, $path, $body, $query)`.

## Laravel

The package auto-registers a service provider and a `Glytos` facade. Set your key in
`.env` (optionally the base URL and environment):

```dotenv
GLYTOS_API_KEY=gly_...
# GLYTOS_ENVIRONMENT=prod
```

Then inject the client anywhere, or use the facade for ad-hoc calls:

```php
use Glytos\Client;

public function __construct(private Client $glytos) {}

$agents = $this->glytos->workflows->list();

// or, for a quick call:
$overview = \Glytos\Laravel\Glytos::request('GET', '/analytics/overview');
```

Publish the config to customize it: `php artisan vendor:publish --tag=glytos-config`.

## Errors

Non-2xx responses (and transport failures, with status `0`) throw an `ApiException`
carrying the API error code, HTTP status, and the request id. Catch the shared
`ExceptionInterface` to handle every Glytos failure in one place.

```php
use Glytos\Exception\ApiException;

try {
    $glytos->workflows->retrieve('missing');
} catch (ApiException $e) {
    echo $e->status, ' ', $e->errorCode, ' ', $e->getMessage(), ' ', $e->requestId;
}
```

## Webhooks

Verify a delivery came from Glytos before trusting it. Pass the **raw** request body,
the `X-Glytos-Signature` header, and your endpoint secret:

```php
use Glytos\Webhook;

$ok = Webhook::verify($rawBody, $_SERVER['HTTP_X_GLYTOS_SIGNATURE'] ?? '', $webhookSecret);
if (!$ok) {
    http_response_code(400);
    exit;
}
```

## License

MIT
