<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Phone numbers: search carrier inventory, provision, and assign numbers to agents.
 */
final class PhoneNumbers extends AbstractResource
{
    /**
     * Search carrier inventory for available numbers.
     *
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     */
    public function search(array $query): array
    {
        return (array) $this->client->request('GET', '/telephony/numbers/search', null, $query);
    }

    /**
     * List the numbers on your account.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/telephony/numbers');
    }

    /**
     * Provision (buy) a number by its E.164 value.
     *
     * @param array<string, mixed> $body Extra provisioning options (e.g. provider).
     *
     * @return array<mixed>
     */
    public function provision(string $e164, array $body = []): array
    {
        return (array) $this->client->request('POST', '/telephony/numbers', ['e164' => $e164] + $body);
    }

    /**
     * Assign a number to an agent.
     *
     * @param array<string, mixed> $body
     *
     * @return array<mixed>
     */
    public function assign(string $numberUuid, array $body = []): array
    {
        return (array) $this->client->request(
            'POST',
            '/telephony/numbers/' . rawurlencode($numberUuid) . '/assign',
            $body,
        );
    }

    /**
     * Release (delete) a number.
     */
    public function release(string $numberUuid): mixed
    {
        return $this->client->request('DELETE', '/telephony/numbers/' . rawurlencode($numberUuid));
    }
}
