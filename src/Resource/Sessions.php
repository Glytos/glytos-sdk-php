<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Sessions: conversation runs across your agents.
 */
final class Sessions extends AbstractResource
{
    /**
     * List sessions across your agents.
     *
     * @param array<string, mixed> $query
     *
     * @return array<mixed>
     */
    public function list(array $query = []): array
    {
        return (array) $this->client->request('GET', '/sessions', null, $query);
    }
}
