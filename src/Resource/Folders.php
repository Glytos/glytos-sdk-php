<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Folders that group agents inside an environment.
 */
final class Folders extends AbstractResource
{
    /**
     * The folders in the active environment.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/agent-folders');
    }

    /**
     * Create a folder in the active environment.
     *
     * @return array<mixed>
     */
    public function create(string $name): array
    {
        return (array) $this->client->request('POST', '/agent-folders', ['name' => $name]);
    }

    /**
     * Rename a folder.
     *
     * @return array<mixed>
     */
    public function rename(string $folderUuid, string $name): array
    {
        return (array) $this->client->request(
            'PATCH',
            '/agent-folders/' . rawurlencode($folderUuid),
            ['name' => $name],
        );
    }

    /**
     * Delete a folder. The agents filed in it are deleted with it.
     */
    public function delete(string $folderUuid): void
    {
        $this->client->request('DELETE', '/agent-folders/' . rawurlencode($folderUuid));
    }
}
