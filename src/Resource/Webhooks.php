<?php

declare(strict_types=1);

namespace Glytos\Resource;

use Glytos\Webhook;

/**
 * Webhooks: manage endpoints and verify delivery signatures.
 */
final class Webhooks extends AbstractResource
{
    /**
     * List your webhook endpoints.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/webhooks/endpoints');
    }

    /**
     * Create a webhook endpoint subscribed to the given events.
     *
     * @param list<string>         $events
     * @param array<string, mixed> $body   Extra options (e.g. a description).
     *
     * @return array<mixed>
     */
    public function create(string $url, array $events, array $body = []): array
    {
        return (array) $this->client->request(
            'POST',
            '/webhooks/endpoints',
            ['url' => $url, 'events' => $events] + $body,
        );
    }

    /**
     * Delete a webhook endpoint.
     */
    public function delete(int|string $endpointId): mixed
    {
        return $this->client->request('DELETE', '/webhooks/endpoints/' . rawurlencode((string) $endpointId));
    }

    /**
     * The catalog of webhook event types you can subscribe to.
     *
     * @return array<mixed>
     */
    public function events(): array
    {
        return (array) $this->client->request('GET', '/webhooks/events');
    }

    /**
     * Verify a webhook delivery signature. Delegates to {@see Webhook::verify()}; pass
     * the RAW request body, the `X-Glytos-Signature` header, and the endpoint secret.
     */
    public function verify(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $toleranceSeconds = 300,
    ): bool {
        return Webhook::verify($payload, $signatureHeader, $secret, $toleranceSeconds);
    }
}
