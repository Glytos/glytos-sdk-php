<?php

declare(strict_types=1);

namespace Glytos\Tests;

use Glytos\Client;
use Glytos\Exception\ApiException;
use Glytos\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientTest extends TestCase
{
    public function testRequiresAnApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client('');
    }

    public function testSuccessfulRequestDecodesJsonAndSendsAuthHeaders(): void
    {
        $http = $this->recordingClient(new Response(200, ['X-Request-Id' => 'req_1'], '[{"uuid":"wf_1"}]'));
        $client = $this->client($http, environment: 'prod');

        $agents = $client->workflows->list();

        self::assertSame([['uuid' => 'wf_1']], $agents);
        self::assertNotNull($http->lastRequest);
        self::assertSame('gly_test', $http->lastRequest->getHeaderLine('X-API-Key'));
        self::assertSame('prod', $http->lastRequest->getHeaderLine('X-Environment-Id'));
        self::assertSame('GET', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/workflows', (string) $http->lastRequest->getUri());
    }

    public function testCreateSerializesTheJsonBody(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"wf_2"}'));
        $client = $this->client($http);

        $client->workflows->create('My Agent');

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertSame('application/json', $http->lastRequest->getHeaderLine('Content-Type'));
        self::assertSame(['name' => 'My Agent', 'mode' => 'prompt'], json_decode((string) $http->lastRequest->getBody(), true));
    }

    public function testDropsNullQueryParameters(): void
    {
        $http = $this->recordingClient(new Response(200, [], '[]'));
        $client = $this->client($http);

        $client->calls->list(['status' => 'completed', 'agent' => null]);

        self::assertNotNull($http->lastRequest);
        self::assertSame('status=completed', $http->lastRequest->getUri()->getQuery());
    }

    public function testErrorResponseThrowsApiException(): void
    {
        $http = $this->recordingClient(new Response(404, ['X-Request-Id' => 'req_2'], '{"error":{"code":"not_found","message":"Nope"}}'));
        $client = $this->client($http);

        try {
            $client->workflows->retrieve('missing');
            self::fail('expected an ApiException');
        } catch (ApiException $exception) {
            self::assertSame(404, $exception->status);
            self::assertSame('not_found', $exception->errorCode);
            self::assertSame('Nope', $exception->getMessage());
            self::assertSame('req_2', $exception->requestId);
        }
    }

    /**
     * @param ClientInterface&object{lastRequest: ?RequestInterface} $http
     */
    private function client(ClientInterface $http, ?string $environment = null): Client
    {
        $factory = new HttpFactory();

        return new Client('gly_test', Client::DEFAULT_BASE_URL, $environment, $http, $factory, $factory);
    }

    private function recordingClient(ResponseInterface $response): ClientInterface
    {
        return new class ($response) implements ClientInterface {
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
