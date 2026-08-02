<?php

declare(strict_types=1);

namespace Glytos;

/**
 * A conversation with an agent.
 *
 * Created against one agent and carrying its id, so no later call has to repeat
 * it. Pass the object itself wherever a thread is expected.
 */
final class Thread
{
    /**
     * @param list<array<mixed>> $messages Anything the agent opened with; empty for a silent opening.
     * @param array<mixed>       $extra    Everything else the API returned, untouched.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $agent,
        public readonly string $status = '',
        public readonly array $messages = [],
        public readonly array $extra = [],
    ) {
    }

    /**
     * @param array<mixed> $started
     */
    public static function fromSession(string $agent, array $started): self
    {
        $id = (string) ($started['session_uuid'] ?? '');
        $status = (string) ($started['status'] ?? '');
        /** @var list<array<mixed>> $messages */
        $messages = (array) ($started['messages'] ?? []);
        unset($started['session_uuid'], $started['status'], $started['messages']);

        return new self($id, $agent, $status, $messages, $started);
    }
}
