<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Http\Client\Middleware;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\ScriptedTransport;
use Support\Serializer\SerializerManagerFactory;
use Support\Temporal\ManualClock;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\Cache\MemoryCacheStore;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\HttpClient;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\Middleware\CacheMiddleware;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\Instant;

class CacheMiddlewareTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    /**
     * @param array<string, string> $headers
     */
    private static function response(
        array $headers = [],
        string $content = '{"name":"John"}',
        int $statusCode = 200,
    ): ClientResponse {
        $list = [
            new Header('Content-Type', 'application/json'),
        ];

        foreach ($headers as $name => $value) {
            $list[] = new Header($name, $value);
        }

        return new ClientResponse(
            content: $content,
            headers: $list,
            statusCode: $statusCode,
            serializerManager: SerializerManagerFactory::create(),
        );
    }

    private static function clock(): ManualClock
    {
        return new ManualClock(Instant::fromUnixTimestamp(self::NOW));
    }

    private static function client(
        ScriptedTransport $transport,
        ManualClock $clock,
        ?Duration $defaultTtl = null,
        int $maxEntrySize = 1024 * 1024,
    ): HttpClient {
        return new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            baseUrl: 'https://api.example',
            middleware: [
                new CacheMiddleware(
                    store: new MemoryCacheStore($clock),
                    defaultTtl: $defaultTtl,
                    maxEntrySize: $maxEntrySize,
                    clock: $clock,
                ),
            ],
            transport: $transport,
        );
    }

    private static function header(
        ClientRequestInterface $request,
        string $name,
    ): ?string {
        foreach ($request->headers as $header) {
            if ($header->is($name)) {
                return $header->value;
            }
        }

        return null;
    }

    public function testFreshResponsesAreServedFromTheStore(): void
    {
        $clock = self::clock();
        $transport = new ScriptedTransport(
            [
                self::response(
                    [
                        'Cache-Control' => 'public, max-age=60',
                    ],
                ),
                self::response(
                    content: '{"name":"Jane"}',
                ),
            ],
        );

        $client = self::client($transport, $clock);

        $first = $client->get('/users/1', BodyContextFixture::class);
        $cached = $client->get('/users/1', BodyContextFixture::class);

        $clock->advance(61_000);

        $expired = $client->get('/users/1', BodyContextFixture::class);

        self::assertSame('John', $first->name);
        self::assertSame('John', $cached->name);
        self::assertSame('Jane', $expired->name);
        self::assertCount(2, $transport->requests);
        self::assertNull(self::header($transport->requests[1], 'If-None-Match'));
    }

    public function testStaleEntriesAreRevalidated(): void
    {
        $clock = self::clock();
        $transport = new ScriptedTransport(
            [
                self::response(
                    [
                        'Cache-Control' => 'max-age=10',
                        'ETag' => '"v1"',
                        'Last-Modified' => 'Tue, 01 Jan 2030 00:00:00 GMT',
                    ],
                ),
                self::response(
                    headers: [
                        'Cache-Control' => 'max-age=30',
                        'X-Refreshed' => 'yes',
                    ],
                    content: '',
                    statusCode: 304,
                ),
            ],
        );

        $client = self::client($transport, $clock);

        $client->get('/repo');
        $clock->advance(11_000);

        $revalidated = $client->get('/repo');

        $clock->advance(20_000);

        $fresh = $client->get('/repo');

        self::assertSame(200, $revalidated->statusCode);
        self::assertSame('{"name":"John"}', $revalidated->body->getRaw());
        self::assertSame('yes', $revalidated->headers->string('X-Refreshed'));
        self::assertSame(200, $fresh->statusCode);
        self::assertCount(2, $transport->requests);
        self::assertSame('"v1"', self::header($transport->requests[1], 'If-None-Match'));
        self::assertSame('Tue, 01 Jan 2030 00:00:00 GMT', self::header($transport->requests[1], 'If-Modified-Since'));
    }

    public function testNoCacheResponsesAreAlwaysRevalidated(): void
    {
        $clock = self::clock();
        $transport = new ScriptedTransport(
            [
                self::response(
                    [
                        'Cache-Control' => 'no-cache',
                        'ETag' => '"v1"',
                    ],
                ),
                self::response(
                    headers: [
                        'Cache-Control' => 'no-cache',
                    ],
                    content: '',
                    statusCode: 304,
                ),
                self::response(
                    content: '',
                    statusCode: 304,
                ),
            ],
        );

        $client = self::client($transport, $clock);

        $client->get('/repo');

        $revalidated = $client->get('/repo');
        $again = $client->get('/repo');

        self::assertSame('{"name":"John"}', $revalidated->body->getRaw());
        self::assertSame('{"name":"John"}', $again->body->getRaw());
        self::assertCount(3, $transport->requests);
        self::assertSame('"v1"', self::header($transport->requests[1], 'If-None-Match'));
        self::assertSame('"v1"', self::header($transport->requests[2], 'If-None-Match'));
    }

    public function testResponsesThatAreNotCached(): void
    {
        $clock = self::clock();
        $cacheable = [
            'Cache-Control' => 'max-age=60',
        ];

        $responses = [
            self::response($cacheable, statusCode: 500),
            self::response(
                [
                    'Cache-Control' => 'no-store',
                ],
            ),
            self::response(
                [
                    'Cache-Control' => 'max-age=60',
                    'Vary' => '*',
                ],
            ),
            self::response(),
            new ClientResponse(
                content: Stream::fromGenerator(
                    static function (): \Generator {
                        yield 'live';
                    },
                ),
                headers: [
                    new Header('Cache-Control', 'max-age=60'),
                ],
            ),
        ];

        $transport = new ScriptedTransport(
            [
                ...$responses,
                ...$responses,
            ],
        );

        $client = self::client($transport, $clock);

        for ($round = 0; $round < 2; $round++) {
            foreach (\array_keys($responses) as $index) {
                $client->get('/case/' . $index);
            }
        }

        $post = new ScriptedTransport(
            [
                self::response($cacheable),
                self::response($cacheable),
            ],
        );

        $postClient = self::client($post, $clock);

        $postClient->post('/x', body: new JsonBody([]));
        $postClient->post('/x', body: new JsonBody([]));

        $large = new ScriptedTransport(
            [
                self::response($cacheable),
                self::response($cacheable),
            ],
        );

        $largeClient = self::client($large, $clock, maxEntrySize: 5);

        $largeClient->get('/x');
        $largeClient->get('/x');

        self::assertCount(10, $transport->requests);
        self::assertCount(2, $post->requests);
        self::assertCount(2, $large->requests);
    }

    public function testRequestDirectivesAndFailures(): void
    {
        $clock = self::clock();
        $transport = new ScriptedTransport(
            [
                self::response(
                    [
                        'Cache-Control' => 'max-age=60',
                    ],
                ),
                self::response(
                    [
                        'Cache-Control' => 'max-age=60',
                    ],
                    content: '{"name":"Again"}',
                ),
                self::response(),
                HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/x'), HttpClientTimeout::IDLE),
                self::response(
                    content: '',
                    statusCode: 304,
                ),
            ],
        );

        $client = self::client($transport, $clock);

        $client->get('/x');

        $noCache = $client->get(
            url: '/x',
            headers: [
                new Header('Cache-Control', 'no-cache'),
            ],
        );

        $noStore = $client->get(
            url: '/x',
            headers: [
                new Header('Cache-Control', 'no-store'),
            ],
        );

        try {
            $client->get('/failing');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException) {
        }

        $notModified = $client->get('/unknown');
        $cached = $client->get('/x');

        self::assertSame('{"name":"Again"}', $noCache->body->getRaw());
        self::assertSame(200, $noStore->statusCode);
        self::assertSame(304, $notModified->statusCode);
        self::assertSame('{"name":"Again"}', $cached->body->getRaw());
        self::assertCount(5, $transport->requests);
    }

    public function testKeysSeparateCredentialsAndVariants(): void
    {
        $clock = self::clock();
        $varying = [
            'Cache-Control' => 'max-age=60',
            'Vary' => 'Accept-Language',
        ];

        $transport = new ScriptedTransport(
            [
                self::response($varying, '{"name":"alice"}'),
                self::response($varying, '{"name":"bob"}'),
                self::response($varying, '{"name":"danish"}'),
            ],
        );

        $client = self::client($transport, $clock);
        $alice = $client->withHeader(new Header('Authorization', 'Bearer alice'));
        $bob = $client->withHeader(new Header('Authorization', 'Bearer bob'));

        $alice->get('/me');
        $bob->get('/me');

        $aliceAgain = $alice->get('/me');
        $danish = $alice->get(
            url: '/me',
            headers: [
                new Header('Accept-Language', 'da'),
            ],
        );

        self::assertSame('{"name":"alice"}', $aliceAgain->body->getRaw());
        self::assertSame('{"name":"danish"}', $danish->body->getRaw());
        self::assertCount(3, $transport->requests);
    }

    public function testFreshnessFromDefaultTtlExpiresAndAge(): void
    {
        $clock = self::clock();
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(
                    [
                        'Date' => \gmdate('D, d M Y H:i:s \G\M\T', self::NOW),
                        'Expires' => \gmdate('D, d M Y H:i:s \G\M\T', self::NOW + 20),
                    ],
                ),
                self::response(
                    [
                        'Cache-Control' => 'max-age=30',
                        'Age' => '25',
                    ],
                ),
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport, $clock, Duration::fromSeconds(5));

        $client->get('/default');
        $client->get('/expires');
        $client->get('/aged');

        $clock->advance(4_000);

        $client->get('/default');
        $client->get('/expires');
        $client->get('/aged');

        $clock->advance(2_000);

        $client->get('/default');
        $client->get('/expires');

        self::assertSame(
            [
                '/default',
                '/expires',
                '/aged',
                '/default',
            ],
            \array_map(
                static fn (ClientRequestInterface $request): string => $request->url->path,
                $transport->requests,
            ),
        );
    }
}
