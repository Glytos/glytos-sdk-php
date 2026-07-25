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

    public function testPromoteSendsTargetEnvironmentId(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"wf_1"}'));
        $client = $this->client($http);

        $client->workflows->promote('wf_1', 'env_prod');

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/workflows/wf_1/promote', (string) $http->lastRequest->getUri());
        self::assertSame(
            ['target_environment_id' => 'env_prod'],
            json_decode((string) $http->lastRequest->getBody(), true),
        );
    }

    public function testUpdateConfigSendsPutWithConfig(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"wf_1"}'));
        $client = $this->client($http);

        $client->workflows->updateConfig('wf_1', ['voice' => ['provider' => 'openai']]);

        self::assertNotNull($http->lastRequest);
        self::assertSame('PUT', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/workflows/wf_1/config', (string) $http->lastRequest->getUri());
        self::assertSame(
            ['config' => ['voice' => ['provider' => 'openai']]],
            json_decode((string) $http->lastRequest->getBody(), true),
        );
    }

    public function testInstantSendsQueryParametersNotABody(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"num_1"}'));
        $client = $this->client($http);

        $client->phoneNumbers->instant('US');

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertSame('country=US', $http->lastRequest->getUri()->getQuery());
        self::assertSame('', (string) $http->lastRequest->getBody());
        self::assertSame('', $http->lastRequest->getHeaderLine('Content-Type'));
    }

    public function testCampaignsCreateSerializesBody(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"camp_1"}'));
        $client = $this->client($http);

        $client->campaigns->create('Q3 Outreach', 'wf_1', '+15550001111');

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/telephony/campaigns', (string) $http->lastRequest->getUri());
        self::assertSame(
            ['name' => 'Q3 Outreach', 'workflow_uuid' => 'wf_1', 'from_number' => '+15550001111'],
            json_decode((string) $http->lastRequest->getBody(), true),
        );
    }

    public function testToolsUpdateSendsPatchWithOnlyProvidedFields(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"uuid":"tool_1"}'));
        $client = $this->client($http);

        $client->tools->update('tool_1', name: 'Renamed');

        self::assertNotNull($http->lastRequest);
        self::assertSame('PATCH', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/tools/tool_1', (string) $http->lastRequest->getUri());
        self::assertSame(['name' => 'Renamed'], json_decode((string) $http->lastRequest->getBody(), true));
    }

    public function testKnowledgeBaseSearchSerializesBody(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{"results":[]}'));
        $client = $this->client($http);

        $client->knowledgeBase->search('refund policy', topK: 3);

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/knowledge-base/search', (string) $http->lastRequest->getUri());
        self::assertSame(
            ['query' => 'refund policy', 'top_k' => 3],
            json_decode((string) $http->lastRequest->getBody(), true),
        );
    }

    public function testAnalyticsOverviewSendsDaysQuery(): void
    {
        $http = $this->recordingClient(new Response(200, [], '{}'));
        $client = $this->client($http);

        $client->analytics->overview(30);

        self::assertNotNull($http->lastRequest);
        self::assertSame('GET', $http->lastRequest->getMethod());
        self::assertStringEndsWith('/analytics/overview?days=30', (string) $http->lastRequest->getUri());
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
