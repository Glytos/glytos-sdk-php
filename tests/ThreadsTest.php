<?php

declare(strict_types=1);

namespace Glytos\Tests;

use Glytos\Client;
use Glytos\Exception\ApiException;
use Glytos\Exception\InvalidArgumentException;
use Glytos\StreamEvent;
use Glytos\Thread;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Threads, streaming, per-turn instructions and uploads. Mirrors the node and
 * python SDK tests so the surfaces cannot drift apart.
 */
final class ThreadsTest extends TestCase
{
    public function testThreadCreateCarriesTheAgentId(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"session_uuid":"ses_1","status":"in_progress"}'));
        $thread = $this->client($http)->threads->create('wf_1', ['name' => 'Ada']);

        self::assertNotNull($http->lastRequest);
        self::assertStringEndsWith('/workflows/wf_1/sessions', (string) $http->lastRequest->getUri());
        self::assertSame(['variables' => ['name' => 'Ada']], $this->body($http->lastRequest));
        // The agent id rides on the thread so no later call has to repeat it.
        self::assertSame('ses_1', $thread->id);
        self::assertSame('wf_1', $thread->agent);
    }

    public function testTurnSendsPerTurnInstructions(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{}'));
        $client = $this->client($http);
        $client->threads->messages->create(
            new Thread('ses_1', 'wf_1'),
            'hello',
            null,
            'answer in French',
        );

        self::assertNotNull($http->lastRequest);
        self::assertStringEndsWith(
            '/workflows/wf_1/sessions/ses_1/messages',
            (string) $http->lastRequest->getUri(),
        );
        self::assertSame(
            ['content' => 'hello', 'additional_instructions' => 'answer in French'],
            $this->body($http->lastRequest),
        );
    }

    public function testAnIncompleteThreadIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client($this->recordingClient(new Response(200, [], '{}')))
            ->threads->messages->create(new Thread('', 'wf_1'), 'hi');
    }

    public function testStreamYieldsTokensThenTheFinishedRun(): void
    {
        $body = $this->sse(['token', '{"delta":"He"}'], ['token', '{"delta":"llo"}'], ['done', '{"status":"completed"}']);
        $http = $this->recordingClient(new Response(200, ['Content-Type' => 'text/event-stream'], $body));

        $text = '';
        $last = null;
        foreach ($this->client($http)->threads->runs->stream(new Thread('s', 'w'), 'hi') as $event) {
            if ($event->type === 'token') {
                $text .= $event->delta;
            }
            $last = $event;
        }

        self::assertSame('Hello', $text);
        self::assertInstanceOf(StreamEvent::class, $last);
        self::assertSame('done', $last->type);
        self::assertSame('completed', $last->run['status'] ?? null);
    }

    public function testStreamEmitsAFinalEventWithoutATrailingBlankLine(): void
    {
        // The last block has no trailing blank line; it must still be delivered.
        $body = "event: token\ndata: {\"delta\":\"x\"}\n\nevent: done\ndata: {\"status\":\"completed\"}";
        $http = $this->recordingClient(new Response(200, [], $body));

        $types = [];
        foreach ($this->client($http)->threads->runs->stream(new Thread('s', 'w')) as $event) {
            $types[] = $event->type;
        }

        self::assertSame(['token', 'done'], $types);
    }

    public function testStreamRaisesTheApiErrorOnRejection(): void
    {
        $http = $this->recordingClient(new Response(
            402,
            [],
            '{"error":{"code":"insufficient_credit","message":"no credit"}}',
        ));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('no credit');
        foreach ($this->client($http)->threads->runs->stream(new Thread('s', 'w')) as $_event) {
            // consuming the generator is what performs the request
        }
    }

    public function testFoldersAndImports(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{}'));
        $client = $this->client($http);

        $client->folders->create('Sales');
        self::assertNotNull($http->lastRequest);
        self::assertStringEndsWith('/agent-folders', (string) $http->lastRequest->getUri());
        self::assertSame(['name' => 'Sales'], $this->body($http->lastRequest));

        $client->folders->delete('fld_1');
        self::assertSame('DELETE', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/agent-folders/fld_1', (string) $http->lastRequest->getUri());

        $client->imports->assistant(['name' => 'Support']);
        self::assertSame(['assistant' => ['name' => 'Support']], $this->body($http->lastRequest));
    }

    public function testUploadIsMultipartNotJson(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"file_uuid":"f_1"}'));
        $this->client($http)->chat->uploadFile('tok', 'ses_1', 'hello', 'notes.txt');

        self::assertNotNull($http->lastRequest);
        $contentType = $http->lastRequest->getHeaderLine('Content-Type');
        self::assertStringStartsWith('multipart/form-data', $contentType);
        // The boundary has to be declared or the server cannot parse the body.
        self::assertStringContainsString('boundary=', $contentType);

        $raw = (string) $http->lastRequest->getBody();
        self::assertStringContainsString('name="token"', $raw);
        self::assertStringContainsString('filename="notes.txt"', $raw);
    }

    public function testAgentsIsTheSameResourceAsWorkflows(): void
    {
        $client = $this->client($this->recordingClient(new Response(200, [], '{}')));
        self::assertSame($client->workflows, $client->agents);
    }

    /**
     * @param array{0: string, 1: string} ...$blocks
     */
    private function sse(array ...$blocks): string
    {
        $out = '';
        foreach ($blocks as [$name, $data]) {
            $out .= "event: {$name}\ndata: {$data}\n\n";
        }

        return $out;
    }

    /**
     * @return array<mixed>
     */
    private function body(RequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function client(ClientInterface $http, ?string $environment = null): Client
    {
        $factory = new HttpFactory();

        return new Client(
            apiKey: 'gly_test',
            environment: $environment,
            httpClient: $http,
            requestFactory: $factory,
            streamFactory: $factory,
        );
    }

    private function recordingClient(ResponseInterface $response): ClientInterface
    {
        return new class($response) implements ClientInterface {
            public ?RequestInterface $lastRequest = null;

            public function __construct(private readonly ResponseInterface $response)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return $this->response;
            }
        };
    }
}
