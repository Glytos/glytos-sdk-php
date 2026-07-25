<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Calls: start and manage phone and web calls.
 */
final class Calls extends AbstractResource
{
    /**
     * Start an outbound phone call, or run a transient (inline) agent.
     *
     * @param array<string, mixed> $body
     *
     * @return array<mixed>
     */
    public function create(array $body): array
    {
        return (array) $this->client->request('POST', '/calls', $body);
    }

    /**
     * List calls.
     *
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     */
    public function list(array $query = []): array
    {
        $resp = $this->client->request('GET', '/calls', null, $query);

        // The calls endpoint is paginated ({items, total, ...}); return the items.
        return \is_array($resp) && \array_key_exists('items', $resp) ? (array) $resp['items'] : (array) $resp;
    }

    /**
     * Retrieve a call by uuid.
     *
     * @return array<mixed>
     */
    public function retrieve(string $callUuid): array
    {
        return (array) $this->client->request('GET', '/calls/' . rawurlencode($callUuid));
    }

    /**
     * Mint a short-lived, workflow-scoped token for an in-browser web call. Hand the
     * returned `token` and `ws_url` to the browser and connect with `@glytos/web`.
     *
     * @param array<string, mixed>|null $agent Inline (transient) agent config.
     *
     * @return array<mixed>
     */
    public function webToken(?string $workflowUuid = null, ?array $agent = null): array
    {
        $body = [];
        if ($workflowUuid !== null) {
            $body['workflow_uuid'] = $workflowUuid;
        }
        if ($agent !== null) {
            $body['agent'] = $agent;
        }

        return (array) $this->client->request('POST', '/calls/web-token', $body);
    }

    /**
     * Control an in-progress call (e.g. transfer, hang up).
     *
     * @param array<string, mixed> $body
     */
    public function control(string $callUuid, array $body = []): mixed
    {
        return $this->client->request('POST', '/calls/' . rawurlencode($callUuid) . '/control', $body);
    }
}
