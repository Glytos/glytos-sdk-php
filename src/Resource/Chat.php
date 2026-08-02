<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Chat: mint a widget token and drive the hosted text channel.
 */
final class Chat extends AbstractResource
{
    /**
     * Mint a short-lived chat token for an agent's text channel.
     *
     * @return array<mixed>
     */
    public function token(string $workflowUuid): array
    {
        return (array) $this->client->request(
            'POST',
            '/chat/token',
            ['workflow_uuid' => $workflowUuid],
        );
    }

    /**
     * Send a chat message. The turn is authenticated by the body `$token`.
     *
     * @param list<string>|null $images Image data URIs or URLs for the current turn.
     *
     * @return array<mixed>
     */
    public function messages(
        string $token,
        string $content,
        ?string $sessionUuid = null,
        ?array $images = null,
    ): array {
        $body = ['token' => $token, 'content' => $content];
        if ($sessionUuid !== null) {
            $body['session_uuid'] = $sessionUuid;
        }
        if ($images !== null) {
            $body['images'] = $images;
        }

        return (array) $this->client->request('POST', '/chat/messages', $body);
    }

    /**
     * The same turn, delivered as it is written.
     *
     * @param list<string>|null $images
     *
     * @return \Generator<int, \Glytos\StreamEvent>
     */
    public function stream(
        string $token,
        string $content,
        ?string $sessionUuid = null,
        ?array $images = null,
    ): \Generator {
        $body = ['token' => $token, 'content' => $content];
        if ($sessionUuid !== null) {
            $body['session_uuid'] = $sessionUuid;
        }
        if ($images !== null) {
            $body['images'] = $images;
        }

        return $this->client->stream('POST', '/chat/stream', $body);
    }

    /**
     * Attach a file to one conversation. Its text is put in front of the agent for
     * that conversation only - it does not join the knowledge base.
     *
     * @return array<mixed>
     */
    public function uploadFile(
        string $token,
        string $sessionUuid,
        string $content,
        string $filename = 'file',
    ): array {
        return (array) $this->client->upload(
            '/chat/files',
            ['token' => $token, 'session_uuid' => $sessionUuid],
            $filename,
            $content,
        );
    }
}
