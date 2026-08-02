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
     * @param array<string, mixed> $query Optional filters, e.g. `archived` (bool) or
     *                                    `environment` (`"all"`, a kind, or an env uuid).
     *
     * @return array<mixed>
     */
    public function list(array $query = []): array
    {
        return (array) $this->client->request('GET', '/workflows', null, $query);
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

    /**
     * Rename an agent.
     *
     * @return array<mixed>
     */
    public function rename(string $workflowUuid, string $name): array
    {
        return (array) $this->client->request(
            'PATCH',
            '/workflows/' . rawurlencode($workflowUuid),
            ['name' => $name],
        );
    }

    /**
     * Export an agent as portable, secret-free JSON.
     *
     * It imports back through `$glytos->imports->create('glytos', ...)`, on this
     * account or another.
     *
     * @return array<mixed>
     */
    public function export(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($workflowUuid) . '/export',
        );
    }

    /**
     * File an agent into a folder. Both must be in the same environment.
     *
     * @return array<mixed>
     */
    public function moveToFolder(string $workflowUuid, string $folderUuid): array
    {
        return (array) $this->client->request(
            'PATCH',
            '/workflows/' . rawurlencode($workflowUuid),
            ['folder_uuid' => $folderUuid],
        );
    }

    /**
     * Take an agent out of its folder, leaving it ungrouped.
     *
     * @return array<mixed>
     */
    public function removeFromFolder(string $workflowUuid): array
    {
        // Sent as null is what unfiles it; not sent at all would leave it where it is.
        return (array) $this->client->request(
            'PATCH',
            '/workflows/' . rawurlencode($workflowUuid),
            ['folder_uuid' => null],
        );
    }

    /**
     * Duplicate an agent, returning the new copy.
     *
     * @return array<mixed>
     */
    public function duplicate(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/duplicate',
        );
    }

    /**
     * Archive an agent.
     *
     * @return array<mixed>
     */
    public function archive(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/archive',
        );
    }

    /**
     * Restore an archived agent.
     *
     * @return array<mixed>
     */
    public function unarchive(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/unarchive',
        );
    }

    /**
     * Promote an agent to another environment (a move, not a copy).
     *
     * @return array<mixed>
     */
    public function promote(string $workflowUuid, string $targetEnvironmentId): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/promote',
            ['target_environment_id' => $targetEnvironmentId],
        );
    }

    /**
     * List the saved versions of an agent.
     *
     * @return array<mixed>
     */
    public function versions(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($workflowUuid) . '/versions',
        );
    }

    /**
     * Replace the visual-workflow graph definition.
     *
     * @param array<string, mixed> $graph
     *
     * @return array<mixed>
     */
    public function updateDefinition(string $workflowUuid, array $graph): array
    {
        return (array) $this->client->request(
            'PUT',
            '/workflows/' . rawurlencode($workflowUuid) . '/definition',
            ['graph' => $graph],
        );
    }

    /**
     * Replace the agent config (voice, model, behaviour, ...).
     *
     * @param array<string, mixed> $config
     *
     * @return array<mixed>
     */
    public function updateConfig(string $workflowUuid, array $config): array
    {
        return (array) $this->client->request(
            'PUT',
            '/workflows/' . rawurlencode($workflowUuid) . '/config',
            ['config' => $config],
        );
    }

    /**
     * Start a conversation session with an agent.
     *
     * @param array<string, mixed>|null $variables Initial session variables.
     * @param int|string|null           $version   A specific agent version to run.
     *
     * @return array<mixed>
     */
    public function startSession(
        string $workflowUuid,
        ?array $variables = null,
        int|string|null $version = null,
    ): array {
        $body = [];
        if ($variables !== null) {
            $body['variables'] = $variables;
        }
        if ($version !== null) {
            $body['version'] = $version;
        }

        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/sessions',
            $body,
        );
    }

    /**
     * Send a message in a session and get the assistant's reply for that turn.
     *
     * @param list<string>|null $images Image data URIs or URLs for the current turn.
     *
     * @return array<mixed>
     */
    public function sendMessage(
        string $workflowUuid,
        string $sessionUuid,
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): array {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid)
                . '/sessions/' . rawurlencode($sessionUuid) . '/messages',
            ThreadRef::turnBody($content, $images, $instructions),
        );
    }

    /**
     * The same turn, delivered as it is written.
     *
     * @param list<string>|null $images
     *
     * @return \Generator<int, \Glytos\StreamEvent>
     */
    public function streamMessage(
        string $workflowUuid,
        string $sessionUuid,
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): \Generator {
        return $this->client->stream(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid)
                . '/sessions/' . rawurlencode($sessionUuid) . '/messages/stream',
            ThreadRef::turnBody($content, $images, $instructions),
        );
    }

    /**
     * Run a one-shot text conversation against an agent.
     *
     * @param list<array<string, mixed>> $messages Chat messages, e.g. `[['role' => 'user', 'content' => 'Hi']]`.
     *
     * @return array<mixed>
     */
    public function runText(string $workflowUuid, array $messages): array
    {
        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($workflowUuid) . '/runs/text',
            ['messages' => $messages],
        );
    }
}
