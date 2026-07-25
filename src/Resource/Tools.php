<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Tools: saved tools your agents can call (`http`, `static`, or `mcp`).
 */
final class Tools extends AbstractResource
{
    /**
     * List your saved tools.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/tools');
    }

    /**
     * Create a tool. `$kind` is `"http"`, `"static"`, or `"mcp"`.
     *
     * @param array<string, mixed>|null $config     Kind-specific configuration.
     * @param array<string, mixed>|null $parameters JSON-schema parameters for the tool.
     *
     * @return array<mixed>
     */
    public function create(
        string $name,
        string $kind,
        ?string $description = null,
        ?array $config = null,
        ?array $parameters = null,
    ): array {
        $body = ['name' => $name, 'kind' => $kind];
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($config !== null) {
            $body['config'] = $config;
        }
        if ($parameters !== null) {
            $body['parameters'] = $parameters;
        }

        return (array) $this->client->request('POST', '/tools', $body);
    }

    /**
     * Update a tool. Only the arguments you pass are sent.
     *
     * @param array<string, mixed>|null $config
     * @param array<string, mixed>|null $parameters
     *
     * @return array<mixed>
     */
    public function update(
        string $toolUuid,
        ?string $name = null,
        ?string $description = null,
        ?string $kind = null,
        ?array $config = null,
        ?array $parameters = null,
    ): array {
        $body = [];
        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($kind !== null) {
            $body['kind'] = $kind;
        }
        if ($config !== null) {
            $body['config'] = $config;
        }
        if ($parameters !== null) {
            $body['parameters'] = $parameters;
        }

        return (array) $this->client->request('PATCH', '/tools/' . rawurlencode($toolUuid), $body);
    }

    /**
     * Delete a tool.
     */
    public function delete(string $toolUuid): mixed
    {
        return $this->client->request('DELETE', '/tools/' . rawurlencode($toolUuid));
    }
}
