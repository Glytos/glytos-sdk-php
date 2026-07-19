<?php

declare(strict_types=1);

namespace Glytos;

use Glytos\Exception\ApiException;
use Glytos\Exception\InvalidArgumentException;
use Glytos\Resource\Calls;
use Glytos\Resource\PhoneNumbers;
use Glytos\Resource\Sessions;
use Glytos\Resource\Webhooks;
use Glytos\Resource\Workflows;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The Glytos API client.
 *
 * Authenticate with your organization API key (it starts with `gly_`):
 *
 * ```php
 * $glytos = new \Glytos\Client('gly_...');
 * $agents = $glytos->workflows->list();
 * ```
 *
 * The client is framework-agnostic: it uses any installed PSR-18 HTTP client via
 * discovery, or one you inject. Never expose an API key in the browser; for
 * in-browser voice, mint a short-lived token with {@see Calls::webToken()} instead.
 */
final class Client
{
    public const DEFAULT_BASE_URL = 'https://api.glytos.com/api/v1';

    public readonly Workflows $workflows;
    public readonly Calls $calls;
    public readonly PhoneNumbers $phoneNumbers;
    public readonly Sessions $sessions;
    public readonly Webhooks $webhooks;

    private readonly string $apiKey;
    private readonly string $baseUrl;
    private readonly ?string $environment;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;

    /**
     * @param string      $apiKey      Organization API key (starts with `gly_`).
     * @param string      $baseUrl     API base URL; override for a regional stack.
     * @param string|null $environment `"dev"`, `"staging"`, `"prod"`, or an environment
     *                                 uuid. Scopes reads and calls; defaults to the
     *                                 organization's default (Development) environment.
     *                                 New agents are always created in Development.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $environment = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('Glytos: an API key is required.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->environment = $environment;
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

        $this->workflows = new Workflows($this);
        $this->calls = new Calls($this);
        $this->phoneNumbers = new PhoneNumbers($this);
        $this->sessions = new Sessions($this);
        $this->webhooks = new Webhooks($this);
    }

    /**
     * Low-level request against any endpoint. `$path` is relative to the API base
     * (e.g. `"/workflows"`). Returns the decoded JSON body (array), the raw string for
     * a non-JSON body, or null when empty. Throws {@see ApiException} on a non-2xx
     * response or a transport failure.
     *
     * @param array<string, mixed>|null $body  JSON request body.
     * @param array<string, mixed>|null $query Query parameters; null values are dropped.
     *
     * @return array<mixed>|string|null
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        ?array $query = null,
    ): array|string|null {
        $uri = $this->baseUrl . $path;
        if ($query !== null) {
            $filtered = array_filter($query, static fn ($value): bool => $value !== null);
            if ($filtered !== []) {
                $uri .= '?' . http_build_query($filtered);
            }
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('X-API-Key', $this->apiKey)
            ->withHeader('Accept', 'application/json');

        if ($this->environment !== null) {
            $request = $request->withHeader('X-Environment-Id', $this->environment);
        }

        if ($body !== null) {
            $json = json_encode($body, JSON_THROW_ON_ERROR);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new ApiException(0, 'network_error', $exception->getMessage(), null, $exception);
        }

        $status = $response->getStatusCode();
        $requestId = $response->getHeaderLine('X-Request-Id') ?: null;
        $raw = (string) $response->getBody();

        $data = null;
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            $data = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }

        if ($status >= 200 && $status < 300) {
            /** @var array<mixed>|string|null $data */
            return $data;
        }

        $code = 'error';
        $message = $response->getReasonPhrase() ?: 'Request failed';
        if (is_array($data) && isset($data['error']) && is_array($data['error'])) {
            $code = (string) ($data['error']['code'] ?? $code);
            $message = (string) ($data['error']['message'] ?? $message);
        }

        throw new ApiException($status, $code, $message, $requestId);
    }
}
