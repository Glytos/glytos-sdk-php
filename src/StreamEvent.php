<?php

declare(strict_types=1);

namespace Glytos;

/**
 * One Server-Sent Event from a streamed turn.
 *
 * `type` is `token` (`delta` carries the piece), `done` (`run` carries the finished
 * turn, the same payload the non-streamed call returns) or `error`.
 */
final class StreamEvent
{
    /**
     * @param array<mixed>|null $run
     */
    public function __construct(
        public readonly string $type,
        public readonly string $delta = '',
        public readonly ?array $run = null,
        public readonly string $message = '',
    ) {
    }

    /**
     * Turn one raw SSE block ("event: x\ndata: {...}") into a typed event, or null
     * when the block carries no event we understand.
     */
    public static function parse(string $block): ?self
    {
        $name = '';
        $data = [];
        foreach (explode("\n", $block) as $line) {
            if (str_starts_with($line, 'event:')) {
                $name = trim(substr($line, 6));
            } elseif (str_starts_with($line, 'data:')) {
                $data[] = trim(substr($line, 5));
            }
        }
        if ($name === '' || $data === []) {
            return null;
        }

        $decoded = json_decode(implode("\n", $data), true);
        $payload = is_array($decoded) ? $decoded : [];

        return match ($name) {
            'token' => new self('token', delta: (string) ($payload['delta'] ?? '')),
            'error' => new self('error', message: (string) ($payload['message'] ?? 'stream failed')),
            'done' => new self('done', run: $payload),
            default => null,
        };
    }
}
