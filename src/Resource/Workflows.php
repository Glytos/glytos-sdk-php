<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Agents: prompt agents and visual workflows.
 */
final class Workflows extends AbstractResource
{
    /**
     * List your agents (prompt agents and visual workflows).
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/workflows');
    }

    /**
     * Retrieve a single agent by uuid.
     *
     * @return array<mixed>
     */
    public function retrieve(string $workflowUuid): array
    {
        return (array) $this->client->request('GET', '/workflows/' . rawurlencode($workflowUuid));
    }

    /**
     * Create an agent. `$mode` is `"prompt"` (default) or `"workflow"`.
     *
     * @param array<string, mixed>|null $config
     *
     * @return array<mixed>
     */
    public function create(string $name, string $mode = 'prompt', ?array $config = null): array
    {
        $body = ['name' => $name, 'mode' => $mode];
        if ($config !== null) {
            $body['config'] = $config;
        }

        return (array) $this->client->request('POST', '/workflows', $body);
    }

    /**
     * Publish the current draft so the agent goes live.
     *
     * @return array<mixed>
     */
    public function publish(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/publish',
        );
    }

    /**
     * Delete an agent.
     */
    public function delete(string $workflowUuid): mixed
    {
        return $this->client->request('DELETE', '/workflows/' . rawurlencode($workflowUuid));
    }

    /**
     * Ready-made starter workflow graphs.
     *
     * @return array<mixed>
     */
    public function templates(): array
    {
        return (array) $this->client->request('GET', '/workflows/templates');
    }

    /**
     * Full detail for one session of an agent (transcript, cost, latency, ...).
     *
     * @return array<mixed>
     */
    public function session(string $workflowUuid, string $sessionUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($workflowUuid) . '/sessions/' . rawurlencode($sessionUuid),
        );
    }

    /**
     * The run-event log for a session (routing decisions, tool calls, ...).
     *
     * @return array<mixed>
     */
    public function sessionEvents(string $workflowUuid, string $sessionUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($workflowUuid)
                . '/sessions/' . rawurlencode($sessionUuid) . '/events',
        );
    }
}
