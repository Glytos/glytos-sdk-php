<?php

declare(strict_types=1);

namespace Glytos\Resource;

use Glytos\Client;
use Glytos\Exception\InvalidArgumentException;
use Glytos\StreamEvent;
use Glytos\Thread;

/**
 * Conversations with a text agent, in the vocabulary the rest of the industry uses:
 * a thread holds the conversation, a run is one turn on it.
 *
 * The same session API `$glytos->agents` exposes, shaped so code written against a
 * thread/run model reads the same here.
 */
final class Threads extends AbstractResource
{
    public readonly ThreadMessages $messages;
    public readonly ThreadRuns $runs;

    public function __construct(Client $client)
    {
        parent::__construct($client);
        $this->messages = new ThreadMessages($client);
        $this->runs = new ThreadRuns($client);
    }

    /**
     * Open a conversation with an agent.
     *
     * @param array<string, mixed>|null $variables
     */
    public function create(
        string $agent,
        ?array $variables = null,
        int|string|null $version = null,
    ): Thread {
        $body = [];
        if ($variables !== null) {
            $body['variables'] = $variables;
        }
        if ($version !== null) {
            $body['version'] = $version;
        }

        $started = (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($agent) . '/sessions',
            $body,
        );

        return Thread::fromSession($agent, $started);
    }

    /**
     * The conversation so far, with its variables and cost.
     *
     * @return array<mixed>
     */
    public function retrieve(Thread $thread): array
    {
        [$agent, $id] = ThreadRef::ids($thread);

        return (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($agent) . '/sessions/' . rawurlencode($id),
        );
    }
}

/**
 * The ids behind a thread, and the turn body shared by the plain and streamed calls.
 *
 * @internal
 */
final class ThreadRef
{
    /**
     * @return array{0: string, 1: string}
     */
    public static function ids(Thread $thread): array
    {
        if ($thread->agent === '' || $thread->id === '') {
            throw new InvalidArgumentException('Glytos: a thread reference needs both id and agent');
        }

        return [$thread->agent, $thread->id];
    }

    /**
     * @param list<string>|null $images
     *
     * @return array<string, mixed>
     */
    public static function turnBody(
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): array {
        $body = ['content' => $content];
        if ($images !== null) {
            $body['images'] = $images;
        }
        if ($instructions !== null) {
            $body['additional_instructions'] = $instructions;
        }

        return $body;
    }
}

/**
 * A thread's messages.
 */
final class ThreadMessages extends AbstractResource
{
    /**
     * Add a user message and run the agent on it. Returns that turn's reply.
     *
     * @param list<string>|null $images
     *
     * @return array<mixed>
     */
    public function create(
        Thread $thread,
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): array {
        [$agent, $id] = ThreadRef::ids($thread);

        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($agent) . '/sessions/' . rawurlencode($id) . '/messages',
            ThreadRef::turnBody($content, $images, $instructions),
        );
    }

    /**
     * Every message in the conversation, oldest first.
     *
     * @return array<mixed>
     */
    public function list(Thread $thread): array
    {
        [$agent, $id] = ThreadRef::ids($thread);
        $detail = (array) $this->client->request(
            'GET',
            '/workflows/' . rawurlencode($agent) . '/sessions/' . rawurlencode($id),
        );

        /** @var array<mixed> $transcript */
        $transcript = (array) ($detail['transcript'] ?? []);

        return $transcript;
    }
}

/**
 * One turn on a thread.
 */
final class ThreadRuns extends AbstractResource
{
    /**
     * Run one turn and wait for it. A turn completes before it returns, so there is
     * no run to poll: the reply is already in the result.
     *
     * @param list<string>|null $images
     *
     * @return array<mixed>
     */
    public function create(
        Thread $thread,
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): array {
        [$agent, $id] = ThreadRef::ids($thread);

        return (array) $this->client->request(
            'POST',
            '/workflows/' . rawurlencode($agent) . '/sessions/' . rawurlencode($id) . '/messages',
            ThreadRef::turnBody($content, $images, $instructions),
        );
    }

    /**
     * The same turn, delivered as it is written.
     *
     * @param list<string>|null $images
     *
     * @return \Generator<int, StreamEvent>
     */
    public function stream(
        Thread $thread,
        string $content = '',
        ?array $images = null,
        ?string $instructions = null,
    ): \Generator {
        [$agent, $id] = ThreadRef::ids($thread);

        return $this->client->stream(
            'POST',
            '/workflows/' . rawurlencode($agent) . '/sessions/' . rawurlencode($id) . '/messages/stream',
            ThreadRef::turnBody($content, $images, $instructions),
        );
    }
}
