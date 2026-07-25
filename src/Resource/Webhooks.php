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
     * Update a webhook endpoint. Only the arguments you pass are sent.
     *
     * @param list<string>|null              $events
     * @param array<string, string>|null     $headers     Extra headers sent with each delivery.
     *
     * @return array<mixed>
     */
    public function update(
        int $endpointId,
        ?string $url = null,
        ?array $events = null,
        ?bool $isActive = null,
        ?int $timeoutSeconds = null,
        ?array $headers = null,
        ?string $authHeader = null,
    ): array {
        $body = [];
        if ($url !== null) {
            $body['url'] = $url;
        }
        if ($events !== null) {
            $body['events'] = $events;
        }
        if ($isActive !== null) {
            $body['is_active'] = $isActive;
        }
        if ($timeoutSeconds !== null) {
            $body['timeout_seconds'] = $timeoutSeconds;
        }
        if ($headers !== null) {
            $body['headers'] = $headers;
        }
        if ($authHeader !== null) {
            $body['auth_header'] = $authHeader;
        }

        return (array) $this->client->request(
            'PATCH',
            '/webhooks/endpoints/' . rawurlencode((string) $endpointId),
            $body,
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
     * List recent webhook deliveries (for debugging), optionally filtered.
     *
     * @return array<mixed>
     */
    public function deliveries(
        ?string $eventType = null,
        ?string $status = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $resp = $this->client->request(
            'GET',
            '/webhooks/deliveries',
            null,
            [
                'event_type' => $eventType,
                'status' => $status,
                'limit' => $limit,
                'offset' => $offset,
            ],
        );

        // Deliveries are paginated ({items, total, ...}); return the items.
        return \is_array($resp) && \array_key_exists('items', $resp) ? (array) $resp['items'] : (array) $resp;
    }

    /**
     * Redeliver a past webhook event.
     *
     * @return array<mixed>
     */
    public function redeliver(int $deliveryId): array
    {
        return (array) $this->client->request(
            'POST',
            '/webhooks/deliveries/' . rawurlencode((string) $deliveryId) . '/redeliver',
        );
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
