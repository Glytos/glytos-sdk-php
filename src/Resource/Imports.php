<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Bring an agent over from another platform.
 */
final class Imports extends AbstractResource
{
    /**
     * The platforms an agent can be brought over from.
     *
     * @return array<mixed>
     */
    public function sources(): array
    {
        return (array) $this->client->request('GET', '/imports/sources');
    }

    /**
     * Bring an agent over from another platform's export.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<mixed>
     */
    public function create(string $source, array $payload): array
    {
        return (array) $this->client->request(
            'POST',
            '/imports/' . rawurlencode($source),
            ['payload' => $payload],
        );
    }

    /**
     * Bring over an assistant definition, tools and all.
     *
     * @param array<string, mixed> $assistant
     *
     * @return array<mixed>
     */
    public function assistant(array $assistant): array
    {
        return (array) $this->client->request(
            'POST',
            '/imports/openai-assistant',
            ['assistant' => $assistant],
        );
    }
}
