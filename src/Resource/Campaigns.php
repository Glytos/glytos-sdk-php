<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Campaigns: outbound calling campaigns over a phone number.
 */
final class Campaigns extends AbstractResource
{
    /**
     * List your campaigns.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/telephony/campaigns');
    }

    /**
     * Create a campaign that dials `$contacts` with an agent from `$fromNumber`.
     *
     * @param list<array<string, mixed>>|null $contacts Contact records to dial.
     *
     * @return array<mixed>
     */
    public function create(
        string $name,
        string $workflowUuid,
        string $fromNumber,
        ?array $contacts = null,
    ): array {
        $body = [
            'name' => $name,
            'workflow_uuid' => $workflowUuid,
            'from_number' => $fromNumber,
        ];
        if ($contacts !== null) {
            $body['contacts'] = $contacts;
        }

        return (array) $this->client->request('POST', '/telephony/campaigns', $body);
    }

    /**
     * Retrieve a campaign by uuid.
     *
     * @return array<mixed>
     */
    public function retrieve(string $campaignUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/telephony/campaigns/' . rawurlencode($campaignUuid),
        );
    }

    /**
     * Start a campaign.
     *
     * @return array<mixed>
     */
    public function start(string $campaignUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/telephony/campaigns/' . rawurlencode($campaignUuid) . '/start',
        );
    }

    /**
     * Sync a campaign's contacts from a remote source URL.
     *
     * @return array<mixed>
     */
    public function syncContacts(string $campaignUuid, string $sourceUrl): array
    {
        return (array) $this->client->request(
            'POST',
            '/telephony/campaigns/' . rawurlencode($campaignUuid) . '/contacts/sync',
            ['source_url' => $sourceUrl],
        );
    }
}
