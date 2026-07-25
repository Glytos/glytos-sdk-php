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
}
