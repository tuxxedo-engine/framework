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

namespace Unit\Http\Client;

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\CallbackMiddleware;
use Support\Http\Client\ScriptedTransport;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\File\FileInterface;
use Tuxxedo\File\Storage\StorageException;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Http\Client\Auth\BearerAuth;
use Tuxxedo\Http\Client\Auth\ClientCredentialsAuth;
use Tuxxedo\Http\Client\Auth\HeaderAuth;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\ClientCertificate;
use Tuxxedo\Http\Client\ClientProgressInterface;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\ClientResponseInfo;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\Decoder\CsvDecoder;
use Tuxxedo\Http\Client\Decoder\JsonDecoder;
use Tuxxedo\Http\Client\HttpClient;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\HttpRules;
use Tuxxedo\Http\Client\MemoryCookieJar;
use Tuxxedo\Http\Client\PendingResponseInterface;
use Tuxxedo\Http\Client\Proxy;
use Tuxxedo\Http\Client\RedirectPolicy;
use Tuxxedo\Http\Client\RetryPolicy;
use Tuxxedo\Http\Client\Transport\TransportBatchInterface;
use Tuxxedo\Http\Client\Transport\TransportInterface;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\SameSite;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\FixedClock;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Version;

class HttpClientTest extends TestCase
{
    /**
     * @param HeaderInterface[] $headers
     */
    private static function response(
        int $statusCode = 200,
        string $content = '',
        array $headers = [],
        string $url = 'https://api.example/v1/',
    ): ClientResponse {
        return new ClientResponse(
            content: $content,
            headers: $headers,
            statusCode: $statusCode,
            info: new ClientResponseInfo(
                [
                    'url' => $url,
                    'http_code' => $statusCode,
                ],
            ),
            serializerManager: SerializerManagerFactory::create(),
        );
    }

    private static function json(
        string $content,
        int $statusCode = 200,
    ): ClientResponse {
        return self::typed($content, 'application/json', $statusCode);
    }

    private static function typed(
        string $content,
        string $contentType,
        int $statusCode = 200,
    ): ClientResponse {
        return self::response(
            statusCode: $statusCode,
            content: $content,
            headers: [
                new Header('Content-Type', $contentType),
            ],
        );
    }

    private static function redirect(
        int $statusCode,
        string $location,
        string $url = 'https://api.example/v1/old',
    ): ClientResponse {
        return self::response(
            statusCode: $statusCode,
            headers: [
                new Header('Location', $location),
            ],
            url: $url,
        );
    }

    private static function retryAfter(
        int $statusCode,
        string $retryAfter,
    ): ClientResponse {
        return self::response(
            statusCode: $statusCode,
            headers: [
                new Header('Retry-After', $retryAfter),
            ],
        );
    }

    private static function client(
        ScriptedTransport $transport,
        ?string $baseUrl = 'https://api.example/v1',
    ): HttpClient {
        return new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            baseUrl: $baseUrl,
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

    private static function fastRetry(
        int $times = 1,
        bool $retryNonIdempotent = false,
    ): RetryPolicy {
        return new RetryPolicy(
            times: $times,
            delay: Duration::fromMicroseconds(1),
            maxDelay: Duration::fromMicroseconds(1),
            retryNonIdempotent: $retryNonIdempotent,
        );
    }

    public function testDefaults(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
            ],
        );

        $response = self::client($transport)->get('/users');
        $options = $transport->options[0];

        self::assertSame(200, $response->statusCode);
        self::assertSame(10, $options->connectTimeout->seconds);
        self::assertSame(30, $options->timeout->seconds);
        self::assertSame(30, $options->idleTimeout?->seconds);
        self::assertSame(16 * 1024 * 1024, $options->maxResponseSize);
        self::assertNull($options->httpVersion);
        self::assertNull($options->proxy);
        self::assertSame('Tuxxedo Engine', self::header($transport->requests[0], 'User-Agent'));
        self::assertNull($transport->requests[0]->httpVersion);
    }

    public function testUserAgent(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            exposeEngineVersion: true,
            transport: $transport,
        );

        $client->get('https://api.example/');
        $client->withUserAgent('Shop/1.0')->get('https://api.example/');
        $client->get(
            url: 'https://api.example/',
            headers: [
                new Header('user-agent', 'Own/2.0'),
            ],
        );

        self::assertSame('Tuxxedo Engine/' . Version::SIMPLE, self::header($transport->requests[0], 'User-Agent'));
        self::assertSame('Shop/1.0', self::header($transport->requests[1], 'User-Agent'));
        self::assertSame('Own/2.0', self::header($transport->requests[2], 'User-Agent'));
        self::assertCount(1, $transport->requests[2]->headers);
    }

    #[RequiresPhpExtension('curl')]
    public function testCurlIsTheDefaultTransport(): void
    {
        $client = new HttpClient(SerializerManagerFactory::create());

        self::assertNotSame($client, $client->withTimeout(Duration::fromSeconds(1)));
    }

    #[RequiresPhpExtension('curl')]
    public function testTheContainerBuildsASharedDefaultClient(): void
    {
        $container = new Container();

        $container->singleton($container);

        $client = $container->resolve(HttpClientInterface::class);

        self::assertInstanceOf(HttpClient::class, $client);
        self::assertSame($client, $container->resolve(HttpClientInterface::class));
    }

    /**
     * @return \Generator<array{0: Method, 1: \Closure(HttpClientInterface): mixed, 2: bool}>
     */
    public static function verbDataProvider(): \Generator
    {
        yield [
            Method::GET,
            static fn (HttpClientInterface $client): mixed => $client->get('/x'),
            false,
        ];

        yield [
            Method::HEAD,
            static fn (HttpClientInterface $client): mixed => $client->head('/x'),
            false,
        ];

        yield [
            Method::POST,
            static fn (HttpClientInterface $client): mixed => $client->post('/x'),
            false,
        ];

        yield [
            Method::PUT,
            static fn (HttpClientInterface $client): mixed => $client->put('/x'),
            false,
        ];

        yield [
            Method::DELETE,
            static fn (HttpClientInterface $client): mixed => $client->delete('/x'),
            false,
        ];

        yield [
            Method::CONNECT,
            static fn (HttpClientInterface $client): mixed => $client->connect('/x'),
            false,
        ];

        yield [
            Method::OPTIONS,
            static fn (HttpClientInterface $client): mixed => $client->options('/x'),
            false,
        ];

        yield [
            Method::TRACE,
            static fn (HttpClientInterface $client): mixed => $client->trace('/x'),
            false,
        ];

        yield [
            Method::PATCH,
            static fn (HttpClientInterface $client): mixed => $client->patch('/x'),
            false,
        ];

        yield [
            Method::QUERY,
            static fn (HttpClientInterface $client): mixed => $client->query('/x'),
            false,
        ];

        yield [
            Method::GET,
            static fn (HttpClientInterface $client): mixed => $client->getList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::POST,
            static fn (HttpClientInterface $client): mixed => $client->postList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::PUT,
            static fn (HttpClientInterface $client): mixed => $client->putList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::DELETE,
            static fn (HttpClientInterface $client): mixed => $client->deleteList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::CONNECT,
            static fn (HttpClientInterface $client): mixed => $client->connectList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::OPTIONS,
            static fn (HttpClientInterface $client): mixed => $client->optionsList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::TRACE,
            static fn (HttpClientInterface $client): mixed => $client->traceList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::PATCH,
            static fn (HttpClientInterface $client): mixed => $client->patchList('/x', BodyContextFixture::class),
            true,
        ];

        yield [
            Method::QUERY,
            static fn (HttpClientInterface $client): mixed => $client->queryList('/x', BodyContextFixture::class),
            true,
        ];
    }

    /**
     * @param \Closure(HttpClientInterface): mixed $call
     */
    #[DataProvider('verbDataProvider')]
    public function testVerbs(
        Method $method,
        \Closure $call,
        bool $list,
    ): void {
        $transport = new ScriptedTransport(
            [
                $list
                    ? self::json('[{"name":"John"}]')
                    : self::response(),
            ],
        );

        $result = $call(self::client($transport));

        self::assertSame($method, $transport->requests[0]->method);
        self::assertSame('https://api.example/v1/x', (string) $transport->requests[0]->url);
        self::assertSame(
            $list,
            \is_array($result),
        );
    }

    /**
     * @return \Generator<array{0: string|null, 1: string, 2: string}>
     */
    public static function baseUrlDataProvider(): \Generator
    {
        yield [
            'https://api.example/v1',
            'users',
            'https://api.example/v1/users',
        ];

        yield [
            'https://api.example/v1',
            '/users',
            'https://api.example/v1/users',
        ];

        yield [
            'https://api.example/v1/',
            'users',
            'https://api.example/v1/users',
        ];

        yield [
            'https://api.example/v1/',
            '/users',
            'https://api.example/v1/users',
        ];

        yield [
            'https://api.example/v1',
            '?page=2',
            'https://api.example/v1?page=2',
        ];

        yield [
            'https://api.example/v1',
            'https://other.example/x',
            'https://other.example/x',
        ];

        yield [
            'https://api.example/v1',
            '//cdn.example/x',
            'https://cdn.example/x',
        ];

        yield [
            null,
            'https://api.example/a',
            'https://api.example/a',
        ];
    }

    #[DataProvider('baseUrlDataProvider')]
    public function testBaseUrlJoining(
        ?string $baseUrl,
        string $reference,
        string $expected,
    ): void {
        $transport = new ScriptedTransport(
            [
                self::response(),
            ],
        );

        self::client($transport, $baseUrl)->get($reference);

        self::assertSame($expected, (string) $transport->requests[0]->url);
    }

    public function testWithBaseUrl(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport, null);

        $client->withBaseUrl('https://a.example/api')->get('x');
        $client->withBaseUrl(Url::parse('https://b.example/api'))->get('x');

        self::assertSame('https://a.example/api/x', (string) $transport->requests[0]->url);
        self::assertSame('https://b.example/api/x', (string) $transport->requests[1]->url);
    }

    public function testDotSegmentsAreRefused(): void
    {
        $transport = new ScriptedTransport();

        foreach (
            [
                '../admin',
                '/a/./b',
                '/a/..',
            ] as $reference
        ) {
            try {
                self::client($transport)->get($reference);

                self::fail('Expected HttpClientException was not thrown');
            } catch (HttpClientException $exception) {
                self::assertSame(HttpClientFailure::REQUEST, $exception->failure);
            }
        }

        self::assertSame([], $transport->requests);
    }

    public function testQueryEncoding(): void
    {
        $stringable = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'text';
            }
        };

        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport)->withQuery(
            [
                'api' => 'v 1',
                'tags' => [
                    'a',
                    'b',
                ],
            ],
        );

        $client->get(
            url: '/items?x=1',
            query: [
                'filter' => [
                    'state' => 'open',
                ],
                'skip' => null,
                'yes' => true,
                'no' => false,
                'method' => Method::GET,
                'site' => SameSite::LAX,
                'text' => $stringable,
                'object' => new \stdClass(),
            ],
        );

        $client->withBracketQueryArrays(false)->get('/items');
        $client->withQuery(
            [
                'only' => 1,
            ],
            replace: true,
        )->get('/items');

        self::assertSame(
            'https://api.example/v1/items?x=1&api=v%201&tags%5B%5D=a&tags%5B%5D=b&filter%5Bstate%5D=open&yes=1&no=0&method=GET&site=Lax&text=text&object=',
            (string) $transport->requests[0]->url,
        );

        self::assertSame('https://api.example/v1/items?api=v%201&tags=a&tags=b', (string) $transport->requests[1]->url);
        self::assertSame('https://api.example/v1/items?only=1', (string) $transport->requests[2]->url);
        self::assertSame([], $transport->requests[0]->query);
    }

    public function testDefaultHeaders(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport)->withHeaders(
            [
                new Header('X-A', '1'),
                new Header('X-B', '2'),
            ],
        );

        $client->get(
            url: '/x',
            headers: [
                new Header('x-b', 'own'),
            ],
        );

        $client->withHeader(new Header('X-A', 'replaced'), replace: true)->get('/x');
        $client->withoutHeader('x-a')->get('/x');

        self::assertSame('1', self::header($transport->requests[0], 'X-A'));
        self::assertSame('own', self::header($transport->requests[0], 'X-B'));
        self::assertSame('replaced', self::header($transport->requests[1], 'X-A'));
        self::assertNull(self::header($transport->requests[2], 'X-A'));
    }

    public function testMapsDtos(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"data":{"name":"John","age":30}}'),
                self::json('[{"name":"John"},{"name":"Jane"}]'),
                self::typed('<person><name>Ada</name></person>', 'application/xml'),
                self::typed('{"name":"Bob"}', 'text/plain'),
                self::json('{"quantity":-2}'),
            ],
        );

        $client = self::client($transport);

        $user = $client->get(
            url: '/users/1',
            as: BodyContextFixture::class,
            key: 'data',
            only: [
                'name',
            ],
        );

        $users = $client->getList('/users', BodyContextFixture::class);
        $xml = $client->get('/users/2', BodyContextFixture::class);
        $forced = $client->withDecoder(new JsonDecoder())->get('/users/3', BodyContextFixture::class);
        $unvalidated = $client->withSkipValidation(true)->get('/x', Validated::class);

        self::assertSame('John', $user->name);
        self::assertSame(0, $user->age);
        self::assertSame('Jane', $users[1]->name);
        self::assertSame('Ada', $xml->name);
        self::assertSame('Bob', $forced->name);
        self::assertSame(-2, $unvalidated->quantity);
    }

    /**
     * @return \Generator<array{0: ClientResponse, 1: \Closure(HttpClient): mixed, 2: HttpClientFailure, 3: class-string<\Throwable>|null}>
     */
    public static function decodingFailureDataProvider(): \Generator
    {
        yield [
            self::json('{"name":"John"}', 404),
            static fn (HttpClient $client): mixed => $client->get('/x', BodyContextFixture::class),
            HttpClientFailure::STATUS,
            null,
        ];

        yield [
            self::json('{"name":"John"}', 204),
            static fn (HttpClient $client): mixed => $client->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            null,
        ];

        yield [
            self::json(''),
            static fn (HttpClient $client): mixed => $client->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            null,
        ];

        yield [
            self::typed('{}', 'text/plain'),
            static fn (HttpClient $client): mixed => $client->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            null,
        ];

        yield [
            self::json('{nope'),
            static fn (HttpClient $client): mixed => $client->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            SerializerInputException::class,
        ];

        yield [
            self::json('{nope'),
            static fn (HttpClient $client): mixed => $client->getList('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            SerializerInputException::class,
        ];

        yield [
            self::json('{"quantity":-2}'),
            static fn (HttpClient $client): mixed => $client->get('/x', Validated::class),
            HttpClientFailure::BODY,
            ValidationException::class,
        ];

        yield [
            self::json('[{"quantity":-2}]'),
            static fn (HttpClient $client): mixed => $client->getList('/x', Validated::class),
            HttpClientFailure::BODY,
            ValidationException::class,
        ];

        yield [
            self::json('{"name":"John","extra":1}'),
            static fn (HttpClient $client): mixed => $client->withSkipUnknownKeys(false)->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            ValidationException::class,
        ];

        yield [
            self::typed("name\nJohn\n", 'text/csv'),
            static fn (HttpClient $client): mixed => $client->withDecoder(new CsvDecoder())->get('/x', BodyContextFixture::class),
            HttpClientFailure::BODY,
            HttpException::class,
        ];
    }

    /**
     * @param \Closure(HttpClient): mixed $call
     * @param class-string<\Throwable>|null $previous
     */
    #[DataProvider('decodingFailureDataProvider')]
    public function testDecodingFailures(
        ClientResponse $response,
        \Closure $call,
        HttpClientFailure $failure,
        ?string $previous,
    ): void {
        try {
            $call(
                self::client(
                    new ScriptedTransport(
                        [
                            $response,
                        ],
                    ),
                ),
            );

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame($failure, $exception->failure);
            self::assertSame($response, $exception->response);

            if ($previous !== null) {
                self::assertInstanceOf($previous, $exception->getPrevious());
            }
        }
    }

    public function testFollowsRedirects(): void
    {
        $transport = new ScriptedTransport(
            [
                self::redirect(302, '/v1/new'),
                self::json('{"name":"John"}'),
            ],
        );

        $response = self::client($transport)->get('/old');

        self::assertSame(200, $response->statusCode);
        self::assertTrue($response->info->redirected);
        self::assertSame('/v1/old', $response->info->hops[0]->url->path);
        self::assertSame(302, $response->info->hops[0]->statusCode);
        self::assertSame('{"name":"John"}', $response->body->getRaw());
        self::assertSame('https://api.example/v1/new', (string) $transport->requests[1]->url);
        self::assertSame(Method::GET, $transport->requests[1]->method);
    }

    /**
     * @return \Generator<array{0: int, 1: Method, 2: Method, 3: bool}>
     */
    public static function redirectMethodDataProvider(): \Generator
    {
        yield [
            303,
            Method::POST,
            Method::GET,
            false,
        ];

        yield [
            303,
            Method::HEAD,
            Method::HEAD,
            false,
        ];

        yield [
            301,
            Method::POST,
            Method::GET,
            false,
        ];

        yield [
            302,
            Method::PUT,
            Method::PUT,
            true,
        ];

        yield [
            307,
            Method::POST,
            Method::POST,
            true,
        ];

        yield [
            308,
            Method::PATCH,
            Method::PATCH,
            true,
        ];
    }

    #[DataProvider('redirectMethodDataProvider')]
    public function testRedirectMethods(
        int $statusCode,
        Method $method,
        Method $expected,
        bool $keepsBody,
    ): void {
        $transport = new ScriptedTransport(
            [
                self::redirect($statusCode, '/v1/new'),
                self::response(),
            ],
        );

        self::client($transport)->send(
            new ClientRequest(
                method: $method,
                url: '/old',
                headers: [
                    new Header('Content-Type', 'application/json'),
                ],
                body: new JsonBody([]),
            ),
        );

        $next = $transport->requests[1];

        self::assertSame($expected, $next->method);
        self::assertSame($keepsBody, $next->body !== null);
        self::assertSame($keepsBody, $next->hasHeader('Content-Type'));
    }

    public function testRedirectWithAOneShotBodyFails(): void
    {
        $transport = new ScriptedTransport(
            [
                self::redirect(307, '/v1/new'),
            ],
        );

        try {
            self::client($transport)->post(
                url: '/old',
                body: new RawBody(
                    Stream::fromGenerator(
                        static function (): \Generator {
                            yield 'once';
                        },
                    ),
                ),
            );

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::REPLAY, $exception->failure);
        }
    }

    public function testCredentialsAreDroppedOnCrossOriginRedirects(): void
    {
        $headers = [
            new Header('Authorization', 'Bearer secret'),
            new Header('Cookie', 'session=abc'),
            new Header('Proxy-Authorization', 'Basic xyz'),
            new Header('X-Trace', '1'),
        ];

        $transport = new ScriptedTransport(
            [
                self::redirect(302, 'https://other.example/x'),
                self::response(),
                self::redirect(302, 'https://other.example/x'),
                self::response(),
                self::redirect(302, '/v1/same'),
                self::response(),
            ],
        );

        $client = self::client($transport);

        $client->get(
            url: '/old',
            headers: $headers,
        );

        $client->withRedirects(new RedirectPolicy(keepCredentialsAcrossOrigins: true))->get(
            url: '/old',
            headers: $headers,
        );

        $client->get(
            url: '/old',
            headers: $headers,
        );

        self::assertNull(self::header($transport->requests[1], 'Authorization'));
        self::assertNull(self::header($transport->requests[1], 'Cookie'));
        self::assertNull(self::header($transport->requests[1], 'Proxy-Authorization'));
        self::assertSame('1', self::header($transport->requests[1], 'X-Trace'));
        self::assertSame('Bearer secret', self::header($transport->requests[3], 'Authorization'));
        self::assertSame('Bearer secret', self::header($transport->requests[5], 'Authorization'));
        self::assertSame('session=abc', self::header($transport->requests[5], 'Cookie'));
    }

    /**
     * @return \Generator<array{0: ClientResponse, 1: \Closure(HttpClient): HttpClient}>
     */
    public static function unfollowedRedirectDataProvider(): \Generator
    {
        yield [
            self::redirect(302, 'http://api.example/v1/new'),
            static fn (HttpClient $client): HttpClient => $client,
        ];

        yield [
            self::redirect(302, 'ftp://api.example/file'),
            static fn (HttpClient $client): HttpClient => $client,
        ];

        yield [
            self::redirect(302, 'https://other.example/'),
            static fn (HttpClient $client): HttpClient => $client->withRedirects(new RedirectPolicy(followCrossOrigin: false)),
        ];

        yield [
            self::redirect(302, '/v1/new'),
            static fn (HttpClient $client): HttpClient => $client->withoutRedirects(),
        ];

        yield [
            self::redirect(302, 'not a url'),
            static fn (HttpClient $client): HttpClient => $client,
        ];

        yield [
            self::response(302),
            static fn (HttpClient $client): HttpClient => $client,
        ];
    }

    /**
     * @param \Closure(HttpClient): HttpClient $configure
     */
    #[DataProvider('unfollowedRedirectDataProvider')]
    public function testRedirectsThatAreNotFollowed(
        ClientResponse $response,
        \Closure $configure,
    ): void {
        $transport = new ScriptedTransport(
            [
                $response,
            ],
        );

        self::assertSame($response, $configure(self::client($transport))->get('/old'));
        self::assertCount(1, $transport->requests);
    }

    public function testTooManyRedirects(): void
    {
        $transport = new ScriptedTransport(
            [
                self::redirect(302, '/v1/a'),
                self::redirect(302, '/v1/b'),
            ],
        );

        try {
            self::client($transport)->withRedirects(new RedirectPolicy(max: 1))->get('/old');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::REDIRECT, $exception->failure);
        }
    }

    public function testRetriesTransportFailuresOnIdempotentMethods(): void
    {
        $request = new ClientRequest(Method::GET, 'https://api.example/');
        $transport = new ScriptedTransport(
            [
                HttpClientException::fromTimeout($request, HttpClientTimeout::CONNECT),
                self::response(),
            ],
        );

        $response = self::client($transport)->withRetry(self::fastRetry())->get('/x');

        self::assertSame(200, $response->statusCode);
        self::assertCount(2, $transport->requests);
    }

    public function testDoesNotRetryNonIdempotentMethodsByDefault(): void
    {
        $request = new ClientRequest(Method::POST, 'https://api.example/');
        $failure = HttpClientException::fromTransportError($request, 7, 'Failed to connect');
        $transport = new ScriptedTransport(
            [
                $failure,
                $failure,
                self::response(),
            ],
        );

        $client = self::client($transport)->withRetry(self::fastRetry());

        try {
            $client->post('/x');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertSame(200, $client->withRetry(self::fastRetry(retryNonIdempotent: true))->post('/x')->statusCode);
    }

    public function testRetriesStatusesAndHonoursRetryAfter(): void
    {
        $transport = new ScriptedTransport(
            [
                self::retryAfter(503, '0'),
                self::retryAfter(429, 'Wed, 21 Oct 2015 07:28:00 GMT'),
                self::retryAfter(503, 'soon'),
                self::response(),
            ],
        );

        $response = self::client($transport)->withRetry(self::fastRetry(times: 3))->get('/x');

        self::assertSame(200, $response->statusCode);
        self::assertCount(4, $transport->requests);
    }

    public function testRetriesStopAfterTheLimitOrForOtherStatuses(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(503),
                self::response(503),
                self::response(500),
            ],
        );

        $client = self::client($transport)->withRetry(self::fastRetry());

        self::assertSame(503, $client->get('/x')->statusCode);
        self::assertSame(500, $client->get('/x')->statusCode);
        self::assertCount(3, $transport->requests);
        $disabled = new ScriptedTransport(
            [
                self::response(503),
            ],
        );

        self::assertSame(503, self::client($disabled)->withRetry(null)->get('/x')->statusCode);
    }

    public function testRetriesRewindStreamBodiesAndSkipOneShotBodies(): void
    {
        $seen = [];
        $capture = static function (ClientRequestInterface $request) use (&$seen): ClientResponse {
            $body = $request->body;

            if ($body instanceof RawBody && !\is_string($body->body)) {
                $seen[] = $body->body->getContents();
            }

            return self::response(503);
        };

        $stream = ResourceStream::fromTemporary();

        $stream->write('payload');
        $stream->rewind();

        $transport = new ScriptedTransport(
            [
                $capture,
                $capture,
                self::response(503),
            ],
        );

        $client = self::client($transport)->withRetry(self::fastRetry());

        $client->put(
            url: '/x',
            body: new RawBody($stream),
        );

        $client->put(
            url: '/x',
            body: new RawBody(
                Stream::fromGenerator(
                    static function (): \Generator {
                        yield 'once';
                    },
                ),
            ),
        );

        self::assertSame(
            [
                'payload',
                'payload',
            ],
            $seen,
        );

        self::assertCount(3, $transport->requests);
    }

    public function testCookieJar(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(
                    headers: [
                        new Header('Set-Cookie', 'session=abc; Path=/'),
                    ],
                    url: 'https://api.example/v1/login',
                ),
                self::response(),
                self::redirect(302, '/v1/new'),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport)->withCookieJar(
            new MemoryCookieJar(
                new FixedClock(Instant::fromUnixTimestamp(1_700_000_000)),
            ),
        );

        $client->post('/login');
        $client->get(
            url: '/me',
            headers: [
                new Header('Cookie', 'own=1'),
            ],
        );

        $client->get('/old');
        $client->withoutCookieJar()->get('/me');

        self::assertNull(self::header($transport->requests[0], 'Cookie'));
        self::assertSame('own=1; session=abc', self::header($transport->requests[1], 'Cookie'));
        self::assertSame('session=abc', self::header($transport->requests[3], 'Cookie'));
        self::assertNull(self::header($transport->requests[4], 'Cookie'));
    }

    public function testTransportOptionsFollowTheWithers(): void
    {
        $proxy = new Proxy('http://proxy.internal:3128');
        $certificate = new ClientCertificate('/certs/client.pem');
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport);
        $configured = $client
            ->withConnectTimeout(Duration::fromSeconds(1))
            ->withTimeout(Duration::fromSeconds(2))
            ->withIdleTimeout(null)
            ->withMaxResponseSize(null)
            ->withHttpVersion(HttpVersion::V1_1)
            ->withProxy($proxy)
            ->withClientCertificate($certificate)
            ->withCertificateAuthority('/certs/ca.pem');

        $configured->get('/x');
        $configured->send(
            new ClientRequest(
                method: Method::GET,
                url: '/x',
                httpVersion: HttpVersion::V2_0,
            ),
        );

        $options = $transport->options[0];

        self::assertSame(1, $options->connectTimeout->seconds);
        self::assertSame(2, $options->timeout->seconds);
        self::assertNull($options->idleTimeout);
        self::assertNull($options->maxResponseSize);
        self::assertSame(HttpVersion::V1_1, $options->httpVersion);
        self::assertSame($proxy, $options->proxy);
        self::assertSame($certificate, $options->clientCertificate);
        self::assertSame('/certs/ca.pem', $options->certificateAuthority);
        self::assertSame(HttpVersion::V1_1, $transport->requests[0]->httpVersion);
        self::assertSame(HttpVersion::V2_0, $transport->requests[1]->httpVersion);
        self::assertNotSame($client, $configured);
    }

    public function testSendAndSendList(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"name":"John"}'),
                self::json('[{"name":"Jane"}]'),
            ],
        );

        $client = self::client($transport);

        self::assertSame('John', $client->send(new ClientRequest(Method::GET, '/u'), BodyContextFixture::class)->name);
        self::assertSame('Jane', $client->sendList(new ClientRequest(Method::GET, '/u'), BodyContextFixture::class)[0]->name);
    }

    public function testHeadAcceptsQueryAndHeaders(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
            ],
        );

        self::client($transport)->head(
            url: '/x',
            query: [
                'a' => 1,
            ],
            headers: [
                new Header('X-A', '1'),
            ],
        );

        self::assertSame('https://api.example/v1/x?a=1', (string) $transport->requests[0]->url);
        self::assertSame('1', self::header($transport->requests[0], 'X-A'));
    }

    public function testATransportWithoutResultIsAProtocolError(): void
    {
        $transport = new class () implements TransportInterface {
            public function send(
                iterable $requests,
                TransportOptionsInterface $options,
                int $concurrency = 1,
            ): \Generator {
                yield from [];
            }

            public function stream(
                ClientRequestInterface $request,
                TransportOptionsInterface $options,
            ): ClientResponseInterface|HttpClientException {
                return HttpClientException::fromMissingTransportResult($request);
            }

            public function batch(): TransportBatchInterface
            {
                return new ScriptedTransport()->batch();
            }
        };

        try {
            new HttpClient(
                serializerManager: SerializerManagerFactory::create(),
                transport: $transport,
            )->get('https://api.example/');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::PROTOCOL, $exception->failure);
        }
    }

    public function testAuthIsScopedToTheBaseUrlOrigin(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport)->withAuth(
            new BearerAuth('token'),
            new HeaderAuth('X-Api-Key', 'key'),
        );

        $client->get('/x');
        $client->get('https://other.example/x');
        $client->withAuth()->get('/x');
        self::client($transport, baseUrl: null)->withAuth(new BearerAuth('token'))->get('https://other.example/x');

        self::assertSame('Bearer token', self::header($transport->requests[0], 'Authorization'));
        self::assertSame('key', self::header($transport->requests[0], 'X-Api-Key'));
        self::assertNull(self::header($transport->requests[1], 'Authorization'));
        self::assertNull(self::header($transport->requests[1], 'X-Api-Key'));
        self::assertNull(self::header($transport->requests[2], 'Authorization'));
        self::assertSame('Bearer token', self::header($transport->requests[3], 'Authorization'));
    }

    public function testAuthIsNotAppliedAfterACrossOriginRedirect(): void
    {
        $transport = new ScriptedTransport(
            [
                self::redirect(302, 'https://other.example/x'),
                self::response(),
            ],
        );

        self::client($transport)->withAuth(new BearerAuth('token'))->get('/old');

        self::assertSame('Bearer token', self::header($transport->requests[0], 'Authorization'));
        self::assertNull(self::header($transport->requests[1], 'Authorization'));
    }

    public function testCredentialDefaultHeadersStayOnTheBaseUrlOrigin(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport)->withHeaders(
            [
                new Header('Authorization', 'Bearer default'),
                new Header('X-Trace', '1'),
            ],
        );

        $client->get('/x');
        $client->get('https://other.example/x');
        self::client($transport, baseUrl: null)
            ->withHeader(new Header('Authorization', 'Bearer default'))
            ->get('https://other.example/x');

        self::assertSame('Bearer default', self::header($transport->requests[0], 'Authorization'));
        self::assertNull(self::header($transport->requests[1], 'Authorization'));
        self::assertSame('1', self::header($transport->requests[1], 'X-Trace'));
        self::assertSame('Bearer default', self::header($transport->requests[2], 'Authorization'));
    }

    private static function credentials(): ClientCredentialsAuth
    {
        return new ClientCredentialsAuth(
            tokenUrl: 'https://auth.example/token',
            clientId: 'id',
            clientSecret: 'secret',
            clock: new FixedClock(Instant::fromUnixTimestamp(1_000)),
        );
    }

    public function testUnauthorizedResponsesRefreshAndResendOnce(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"access_token":"abc"}'),
                self::response(401),
                self::json('{"access_token":"def"}'),
                self::response(200),
            ],
        );

        $response = self::client($transport)->withAuth(self::credentials())->get('/x');

        self::assertSame(200, $response->statusCode);
        self::assertSame('Basic ' . \base64_encode('id:secret'), self::header($transport->requests[0], 'Authorization'));
        self::assertSame('Bearer abc', self::header($transport->requests[1], 'Authorization'));
        self::assertSame('Bearer def', self::header($transport->requests[3], 'Authorization'));
    }

    public function testASecondUnauthorizedResponseIsReturned(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"access_token":"abc"}'),
                self::response(401),
                self::json('{"access_token":"def"}'),
                self::response(401),
            ],
        );

        $response = self::client($transport)->withAuth(self::credentials())->get('/x');

        self::assertSame(401, $response->statusCode);
        self::assertSame(4, \sizeof($transport->requests));
    }

    public function testUnauthorizedResponsesAreReturnedWhenNothingRefreshes(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(401),
                self::json('{"access_token":"abc"}'),
                self::response(401),
            ],
        );

        $client = self::client($transport);

        $plain = $client->withAuth(new BearerAuth('token'))->get('/x');
        $oneShot = $client->withAuth(self::credentials())->post(
            url: '/x',
            body: new RawBody(
                Stream::fromGenerator(
                    static function (): \Generator {
                        yield 'once';
                    },
                ),
            ),
        );

        self::assertSame(401, $plain->statusCode);
        self::assertSame(401, $oneShot->statusCode);
        self::assertSame(3, \sizeof($transport->requests));
    }

    public function testRulesFakeResponsesThroughTheWholePipeline(): void
    {
        $transport = new ScriptedTransport();
        $client = self::client($transport)->withRules(
            new HttpRules()
                ->respond(
                    'api.example/v1/old',
                    ClientResponse::empty(
                        302,
                        [
                            new Header('Location', '/v1/new'),
                        ],
                    ),
                )
                ->respond(
                    'api.example/v1/new',
                    ClientResponse::json(
                        [
                            'name' => 'John',
                        ],
                    ),
                ),
        );

        $dto = $client->get('/old', BodyContextFixture::class);

        self::assertSame('John', $dto->name);
        self::assertSame([], $transport->requests);
    }

    public function testEveryRuleLayerMustPass(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
            ],
        );

        $client = self::client($transport, baseUrl: null)
            ->withRules(new HttpRules()->respond('api.example', ClientResponse::text('fake')))
            ->withRules(new HttpRules()->deny('api.example/secret'));

        $fake = $client->get('https://api.example/x');

        try {
            $client->get('https://api.example/secret');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RULE, $exception->failure);
        }

        $client->get('https://other.example/');

        self::assertSame('fake', $fake->body->getRaw());
        self::assertSame(1, \sizeof($transport->requests));
        self::assertNotNull($transport->options[0]->addressCheck);
    }

    public function testRuleFailuresAreRetried(): void
    {
        $failures = 0;
        $client = self::client(new ScriptedTransport())
            ->withRetry(self::fastRetry())
            ->withRules(
                new HttpRules()->fail(
                    'api.example',
                    static function (ClientRequestInterface $request) use (&$failures): HttpClientException {
                        $failures++;

                        return HttpClientException::fromTimeout($request, HttpClientTimeout::CONNECT);
                    },
                ),
            );

        try {
            $client->get('/x');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::TIMEOUT, $exception->failure);
        }

        self::assertSame(2, $failures);
    }

    public function testTheAddressCheckAsksEveryLayer(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
            ],
        );

        $client = self::client($transport);

        $client->get('/x');
        $client->withRules(new HttpRules())->withRules(new HttpRules()->denyPrivateNetworks())->get('/x');

        $check = $transport->options[1]->addressCheck;

        self::assertNull($transport->options[0]->addressCheck);
        self::assertNotNull($check);
        self::assertFalse($check('10.0.0.1'));
        self::assertTrue($check('93.184.216.34'));
    }

    public function testRulesFromTheConstructor(): void
    {
        $client = new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            rules: new HttpRules()->denyUnmatched(),
            transport: new ScriptedTransport(),
        );

        try {
            $client->get('https://api.example/');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RULE, $exception->failure);
        }
    }

    public function testStreamUsesStreamingOptionsAndChecksTheStatus(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'live'),
                self::response(404),
            ],
        );

        $client = self::client($transport);

        $response = $client->stream(
            method: Method::POST,
            url: '/chat',
            body: new JsonBody(
                [
                    'a' => 1,
                ],
            ),
        );

        try {
            $client->stream(Method::GET, '/missing');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::STATUS, $exception->failure);
        }

        self::assertSame('live', $response->body->getRaw());
        self::assertSame(Method::POST, $transport->requests[0]->method);
        self::assertSame(0, $transport->options[0]->timeout->seconds);
        self::assertNull($transport->options[0]->maxResponseSize);
    }

    public function testStreamFollowsRedirects(): void
    {
        $transport = new ScriptedTransport(
            [
                self::redirect(302, '/v1/new'),
                self::response(content: 'moved'),
            ],
        );

        $response = self::client($transport)->stream(Method::GET, '/old');

        self::assertSame('moved', $response->body->getRaw());
        self::assertSame('https://api.example/v1/new', (string) $transport->requests[1]->url);
    }

    public function testDownloadWritesTheFileInPlace(): void
    {
        $path = \sys_get_temp_dir() . '/tuxxedo-download-' . \bin2hex(\random_bytes(4));
        $transport = new ScriptedTransport(
            [
                self::response(content: 'payload'),
            ],
        );

        try {
            $response = self::client($transport)->download('/file', $path);

            self::assertSame('payload', \file_get_contents($path));
            self::assertSame('payload', $response->body->getRaw());
            self::assertSame([], \glob($path . '.*.part'));
        } finally {
            @\unlink($path);
        }
    }

    public function testDownloadFailuresLeaveNoFile(): void
    {
        $path = \sys_get_temp_dir() . '/tuxxedo-download-' . \bin2hex(\random_bytes(4));
        $transport = new ScriptedTransport(
            [
                self::response(404),
                self::response(content: 'too large'),
                self::response(content: 'unwritable'),
            ],
        );

        $client = self::client($transport);
        $failures = [];

        foreach (
            [
                static fn (): mixed => $client->download('/file', $path),
                static fn (): mixed => $client->withMaxDownloadSize(3)->download('/file', $path),
                static fn (): mixed => $client->download('/file', '/nonexistent-' . \bin2hex(\random_bytes(4)) . '/file'),
            ] as $call
        ) {
            try {
                $call();
            } catch (HttpClientException $exception) {
                $failures[] = $exception->failure;
            }
        }

        self::assertSame(
            [
                HttpClientFailure::STATUS,
                HttpClientFailure::SIZE,
                HttpClientFailure::REQUEST,
            ],
            $failures,
        );

        self::assertFileDoesNotExist($path);
        self::assertSame([], \glob($path . '.*.part'));
    }

    public function testStreamSse(): void
    {
        $transport = new ScriptedTransport(
            [
                self::typed("data: one\n\ndata: two\n\n", 'text/event-stream; charset=utf-8'),
                self::typed('data: x', 'text/plain'),
                self::response(content: 'data: x'),
            ],
        );

        $client = self::client($transport);
        $data = [];

        foreach ($client->streamSse(Method::GET, '/events') as $event) {
            $data[] = $event->data;
        }

        $failures = [];

        for ($i = 0; $i < 2; $i++) {
            try {
                $client->streamSse(Method::GET, '/events');
            } catch (HttpClientException $exception) {
                $failures[] = $exception->failure;
            }
        }

        self::assertSame(
            [
                'one',
                'two',
            ],
            $data,
        );

        self::assertSame(
            [
                HttpClientFailure::BODY,
                HttpClientFailure::BODY,
            ],
            $failures,
        );
    }

    public function testStreamJsonAndCsvMapRecords(): void
    {
        $transport = new ScriptedTransport(
            [
                self::typed("{\"name\":\"John\"}\n\n\x1E{\"name\":\"Jane\"}\n", 'application/jsonl'),
                self::typed("name,age\nJohn,30\nJane;x\n", 'text/csv'),
            ],
        );

        $client = self::client($transport);
        $json = [];
        $csv = [];

        foreach ($client->streamJson(Method::GET, '/export', BodyContextFixture::class) as $record) {
            $json[] = $record->name;
        }

        try {
            foreach ($client->streamCsv(Method::GET, '/export.csv', BodyContextFixture::class) as $record) {
                $csv[] = $record->age;
            }

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::BODY, $exception->failure);
        }

        self::assertSame(
            [
                'John',
                'Jane',
            ],
            $json,
        );

        self::assertSame(
            [
                30,
            ],
            $csv,
        );
    }

    /**
     * @param class-string $as
     */
    private static function streamFailure(
        HttpClient $client,
        string $as,
    ): ?\Throwable {
        try {
            \iterator_to_array($client->streamJson(Method::GET, '/export', $as));
        } catch (HttpClientException $exception) {
            return $exception->getPrevious();
        }

        return null;
    }

    public function testStreamedRecordsReportBodyFailures(): void
    {
        $transport = new ScriptedTransport(
            [
                self::typed("{\"quantity\":-2}\n", 'application/jsonl'),
                self::typed("{nope\n", 'application/jsonl'),
            ],
        );

        $client = self::client($transport);
        $invalid = self::streamFailure($client, Validated::class);
        $malformed = self::streamFailure($client, BodyContextFixture::class);

        self::assertInstanceOf(ValidationException::class, $invalid);
        self::assertNotNull($malformed);
        self::assertNotInstanceOf(ValidationException::class, $malformed);
    }

    public function testStrictBaseUrlRefusesOtherOrigins(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::redirect(302, 'https://other.example/x'),
                self::response(),
            ],
        );

        $strict = self::client($transport)->withStrictBaseUrl(true);
        $failures = [];

        $strict->get('/x');

        foreach (
            [
                'https://other.example/x',
                '/old',
            ] as $url
        ) {
            try {
                $strict->get($url);
            } catch (HttpClientException $exception) {
                $failures[] = (string) $exception->request?->url;
            }
        }

        self::client($transport, baseUrl: null)->withStrictBaseUrl(true)->get('https://other.example/x');

        self::assertSame(
            [
                'https://other.example/x',
                'https://other.example/x',
            ],
            $failures,
        );

        self::assertCount(3, $transport->requests);
    }

    public function testDownloadToWritesIntoTheStorage(): void
    {
        $storage = new class () implements StorageInterface {
            /**
             * @var array<string, array{0: string, 1: ?string, 2: ?string}>
             */
            public array $files = [];

            public function read(
                string $key,
            ): FileInterface {
                throw StorageException::fromKeyNotFound($key);
            }

            public function write(
                string $key,
                FileInterface $file,
            ): void {
                $this->files[$key] = [
                    $file->contents(),
                    $file->name,
                    $file->mimeType,
                ];
            }

            public function delete(
                string $key,
            ): void {
            }

            public function exists(
                string $key,
            ): bool {
                return false;
            }

            public function list(
                string $pattern = '**',
            ): iterable {
                return [];
            }
        };

        $transport = new ScriptedTransport(
            [
                self::typed('report', 'text/csv'),
                self::response(content: 'plain'),
            ],
        );

        $client = self::client($transport);

        $response = $client->downloadTo('/report', $storage, 'reports/latest.csv');

        $client->downloadTo('/plain', $storage, 'plain');

        self::assertSame(200, $response->statusCode);
        self::assertSame('', $response->body->getRaw());
        self::assertSame(
            [
                'reports/latest.csv' => [
                    'report',
                    'latest.csv',
                    'text/csv',
                ],
                'plain' => [
                    'plain',
                    'plain',
                    null,
                ],
            ],
            $storage->files,
        );

        self::assertSame([], \glob(\sys_get_temp_dir() . '/tuxxedo-download-*'));
    }

    private static function sse(
        string|\Closure $content,
    ): ClientResponse {
        return new ClientResponse(
            content: $content instanceof \Closure
                ? Stream::fromGenerator($content)
                : $content,
            headers: [
                new Header('Content-Type', 'text/event-stream'),
            ],
            info: new ClientResponseInfo(
                [
                    'url' => 'https://api.example/v1/events',
                    'http_code' => 200,
                ],
            ),
            serializerManager: SerializerManagerFactory::create(),
        );
    }

    public function testStreamSseReconnectsWithTheLastEventId(): void
    {
        $transport = new ScriptedTransport(
            [
                self::sse("retry: 1\nid: 1\ndata: a\n\n"),
                self::sse(
                    static function (): \Generator {
                        yield "id: 2\ndata: b\n\n";
                        yield '';

                        throw HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/events'), HttpClientTimeout::IDLE);
                    },
                ),
                self::sse("data: c\n\n"),
                self::response(204),
            ],
        );

        $data = [];

        foreach (self::client($transport)->streamSse(Method::GET, '/events', reconnects: 5) as $event) {
            $data[] = $event->data;
        }

        self::assertSame(
            [
                'a',
                'b',
                'c',
            ],
            $data,
        );

        self::assertCount(4, $transport->requests);
        self::assertNull(self::header($transport->requests[0], 'Last-Event-ID'));
        self::assertSame('1', self::header($transport->requests[1], 'Last-Event-ID'));
        self::assertSame('2', self::header($transport->requests[2], 'Last-Event-ID'));
        self::assertSame('2', self::header($transport->requests[3], 'Last-Event-ID'));
    }

    public function testStreamSseStopsWithoutReconnects(): void
    {
        $transport = new ScriptedTransport(
            [
                self::sse("retry: 1\ndata: a\n\n"),
                self::sse("retry: 1\ndata: b\n\n"),
                self::response(204),
                self::sse(
                    static function (): \Generator {
                        yield "data: x\n\n";
                        yield '';

                        throw HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/events'), HttpClientTimeout::IDLE);
                    },
                ),
            ],
        );

        $client = self::client($transport);

        $once = \iterator_to_array($client->streamSse(Method::GET, '/events'), false);
        $limited = \iterator_to_array($client->streamSse(Method::GET, '/events', reconnects: 1), false);

        try {
            \iterator_to_array($client->streamSse(Method::GET, '/events'), false);

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::TIMEOUT, $exception->failure);
        }

        self::assertCount(1, $once);
        self::assertSame('b', $limited[0]->data ?? null);
        self::assertCount(4, $transport->requests);
    }

    public function testResumedDownloads(): void
    {
        $path = \sys_get_temp_dir() . '/tuxxedo-resume-' . \bin2hex(\random_bytes(4));
        $transport = new ScriptedTransport(
            [
                new ClientResponse(
                    content: Stream::fromGenerator(
                        static function (): \Generator {
                            yield 'abc';
                            yield '';

                            throw HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/file'), HttpClientTimeout::IDLE);
                        },
                    ),
                    serializerManager: SerializerManagerFactory::create(),
                ),
                self::response(206, 'def'),
                self::response(content: 'restarted'),
                self::response(416),
            ],
        );

        $client = self::client($transport);

        try {
            try {
                $client->download('/file', $path, resume: true);

                self::fail('Expected HttpClientException was not thrown');
            } catch (HttpClientException $exception) {
                self::assertSame(HttpClientFailure::TIMEOUT, $exception->failure);
            }

            $partial = \file_get_contents($path . '.part');

            $client->download('/file', $path, resume: true);

            $resumed = \file_get_contents($path);

            \file_put_contents($path . '.part', 'stale');

            $client->download('/file', $path, resume: true);

            $restarted = \file_get_contents($path);

            \file_put_contents($path . '.part', 'complete');

            $client->download('/file', $path, resume: true);

            self::assertSame('abc', $partial);
            self::assertSame('abcdef', $resumed);
            self::assertSame('restarted', $restarted);
            self::assertSame('complete', \file_get_contents($path));
            self::assertNull(self::header($transport->requests[0], 'Range'));
            self::assertSame('bytes=3-', self::header($transport->requests[1], 'Range'));
            self::assertSame('bytes=5-', self::header($transport->requests[2], 'Range'));
            self::assertSame('bytes=8-', self::header($transport->requests[3], 'Range'));
            self::assertFileDoesNotExist($path . '.part');
        } finally {
            @\unlink($path);
            @\unlink($path . '.part');
        }
    }

    public function testDeferredCallsAreLazyAndWaitedTogether(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"name":"John"}'),
                self::json('[{"name":"Jane"},{"name":"Joe"}]'),
                self::response(204),
            ],
        );

        $client = self::client($transport);
        $deferred = $client->deferred();

        $user = $deferred->get('/users/1', BodyContextFixture::class);
        $users = $deferred->getList('/users', BodyContextFixture::class);
        $ping = $deferred->head('/ping');

        $completedBeforeWait = $user->completed || $users->completed || $ping->completed;

        $client->wait($user, $users, $ping);

        self::assertFalse($completedBeforeWait);
        self::assertSame(3, \sizeof($transport->requests));
        self::assertTrue($user->completed);
        self::assertTrue($user->succeeded);
        self::assertNull($user->exception);
        self::assertSame('John', $user->result()->name);
        self::assertSame('Joe', $users->result()[1]->name);
        self::assertSame(204, $ping->result()->statusCode);
        self::assertSame(Method::HEAD, $transport->requests[2]->method);
    }

    public function testEveryDeferredVerbUsesItsMethod(): void
    {
        $responses = [];

        for ($i = 0; $i < 20; $i++) {
            $responses[] = self::json('[{"name":"John"}]');
        }

        $transport = new ScriptedTransport($responses);
        $client = self::client($transport);
        $deferred = $client->deferred();
        $body = new JsonBody([]);

        $client->wait(
            $deferred->get('/x'),
            $deferred->post('/x', body: $body),
            $deferred->put('/x', body: $body),
            $deferred->delete('/x'),
            $deferred->connect('/x'),
            $deferred->options('/x'),
            $deferred->trace('/x'),
            $deferred->patch('/x', body: $body),
            $deferred->query('/x', body: $body),
            $deferred->getList('/x', BodyContextFixture::class),
            $deferred->postList('/x', BodyContextFixture::class, $body),
            $deferred->putList('/x', BodyContextFixture::class, $body),
            $deferred->deleteList('/x', BodyContextFixture::class),
            $deferred->connectList('/x', BodyContextFixture::class),
            $deferred->optionsList('/x', BodyContextFixture::class),
            $deferred->traceList('/x', BodyContextFixture::class),
            $deferred->patchList('/x', BodyContextFixture::class, $body),
            $deferred->queryList('/x', BodyContextFixture::class, $body),
            $deferred->send(new ClientRequest(Method::GET, '/x')),
            $deferred->sendList(new ClientRequest(Method::GET, '/x'), BodyContextFixture::class),
        );

        self::assertSame(
            [
                'GET',
                'POST',
                'PUT',
                'DELETE',
                'CONNECT',
                'OPTIONS',
                'TRACE',
                'PATCH',
                'QUERY',
                'GET',
                'POST',
                'PUT',
                'DELETE',
                'CONNECT',
                'OPTIONS',
                'TRACE',
                'PATCH',
                'QUERY',
                'GET',
                'GET',
            ],
            \array_map(
                static fn (ClientRequestInterface $request): string => $request->method->name,
                $transport->requests,
            ),
        );
    }

    public function testWaitIsolatesFailures(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(404),
                self::json('{"name":"John"}'),
            ],
        );

        $client = self::client($transport);
        $missing = $client->deferred()->get('/missing', BodyContextFixture::class);
        $found = $client->deferred()->get('/found', BodyContextFixture::class);
        $broken = $client->deferred()->get('/a/../b');

        $client->wait($missing, $found, $broken);

        self::assertTrue($missing->completed);
        self::assertFalse($missing->succeeded);
        self::assertInstanceOf(HttpClientException::class, $missing->exception);
        self::assertSame('John', $found->result()->name);
        self::assertSame(HttpClientFailure::REQUEST, self::pendingFailure($broken));
        self::assertSame(HttpClientFailure::STATUS, self::pendingFailure($missing));
    }

    /**
     * @param PendingResponseInterface<mixed> $pending
     */
    private static function pendingFailure(
        PendingResponseInterface $pending,
    ): ?HttpClientFailure {
        try {
            $pending->result();
        } catch (HttpClientException $exception) {
            return $exception->failure;
        }

        return null;
    }

    public function testResultSendsAPendingOnItsOwn(): void
    {
        $transport = new ScriptedTransport(
            [
                self::json('{"name":"John"}'),
            ],
        );

        $pending = self::client($transport)->deferred()->get('/users/1', BodyContextFixture::class);

        self::assertFalse($pending->completed);
        self::assertSame('John', $pending->result()->name);
        self::assertTrue($pending->succeeded);
        self::assertSame($pending->result(), $pending->result());
        self::assertCount(1, $transport->requests);
    }

    public function testRedirectsAndRetriesReenterTheBatch(): void
    {
        $hits = 0;
        $route = static function (ClientRequestInterface $request) use (&$hits): ClientResponse {
            return match ($request->url->path) {
                '/v1/flaky' => ++$hits === 1
                    ? self::response(503)
                    : self::response(content: 'recovered'),
                '/v1/old' => self::redirect(302, '/v1/new'),
                default => self::response(content: 'moved'),
            };
        };

        $transport = new ScriptedTransport(
            [
                $route,
                $route,
                $route,
                $route,
            ],
        );

        $client = self::client($transport)->withRetry(self::fastRetry());
        $flaky = $client->deferred()->get('/flaky');
        $moved = $client->deferred()->get('/old');

        $client->wait($flaky, $moved);

        self::assertSame('recovered', $flaky->result()->body->getRaw());
        self::assertSame('moved', $moved->result()->body->getRaw());
        self::assertTrue($moved->result()->info->redirected);
        self::assertCount(4, $transport->requests);
    }

    public function testRetryDelaysDoNotBlockOtherPendings(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(503),
                self::response(content: 'second'),
                self::response(content: 'first'),
            ],
        );

        $client = self::client($transport)->withRetry(
            new RetryPolicy(
                times: 1,
                delay: Duration::fromMilliseconds(20),
                maxDelay: Duration::fromMilliseconds(20),
            ),
        );

        $order = [];

        foreach (
            $client->each(
                [
                    'slow' => $client->deferred()->get('/slow'),
                    'fast' => $client->deferred()->get('/fast'),
                ],
            ) as $key => $pending
        ) {
            $order[$key] = $pending->result()->body->getRaw();
        }

        self::assertSame(
            [
                'fast' => 'second',
                'slow' => 'first',
            ],
            $order,
        );
    }

    public function testEachRespectsConcurrencyAndPassesThroughOtherPendings(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'a'),
                self::response(content: 'b'),
            ],
        );

        $client = self::client($transport)->withConcurrency(1);
        $done = $client->deferred()->get('/done');

        $client->wait($done);

        $foreign =
            /**
             * @implements PendingResponseInterface<string>
             */
            new class () implements PendingResponseInterface {
                public bool $completed = false;
                public bool $succeeded = false;
                public ?\Throwable $exception = null;

                public function result(): string
                {
                    return 'foreign';
                }
            };

        $keys = [];

        foreach (
            $client->each(
                (static function () use ($client, $done, $foreign): \Generator {
                    yield 'done' => $done;
                    yield 'foreign' => $foreign;
                    yield 'b' => $client->deferred()->get('/b');
                })(),
                concurrency: 1,
            ) as $key => $pending
        ) {
            $keys[] = $key;
        }

        self::assertSame(
            [
                'done',
                'foreign',
                'b',
            ],
            $keys,
        );

        self::assertCount(2, $transport->requests);
    }

    public function testPendingsFromDifferentClientsShareAWait(): void
    {
        $first = new ScriptedTransport(
            [
                self::response(content: 'one'),
            ],
        );

        $second = new ScriptedTransport(
            [
                self::response(content: 'two'),
            ],
        );

        $one = self::client($first)->deferred()->get('/x');
        $two = self::client($second, baseUrl: 'https://other.example')->deferred()->get('/y');

        self::client(new ScriptedTransport())->wait($one, $two);

        self::assertSame('one', $one->result()->body->getRaw());
        self::assertSame('two', $two->result()->body->getRaw());
        self::assertSame('https://other.example/y', (string) $second->requests[0]->url);
    }

    public function testProgressCallbacksReachTheTransport(): void
    {
        $default = static fn (ClientProgressInterface $progress): bool => true;
        $override = static fn (ClientProgressInterface $progress): bool => true;
        $path = \sys_get_temp_dir() . '/tuxxedo-progress-' . \bin2hex(\random_bytes(4));
        $storage = new class () implements StorageInterface {
            public function read(
                string $key,
            ): FileInterface {
                throw StorageException::fromKeyNotFound($key);
            }

            public function write(
                string $key,
                FileInterface $file,
            ): void {
            }

            public function delete(
                string $key,
            ): void {
            }

            public function exists(
                string $key,
            ): bool {
                return false;
            }

            public function list(
                string $pattern = '**',
            ): iterable {
                return [];
            }
        };

        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
                self::response(content: 'file'),
                self::response(content: 'file'),
                self::response(content: 'stored'),
            ],
        );

        $client = self::client($transport);

        $client->get('/x');
        $client->withProgress($default)->get('/x');
        $client->withProgress($default)->download('/file', $path);
        $client->withProgress($default)->download('/file', $path, progress: $override);
        $client->downloadTo('/file', $storage, 'stored', progress: $override);

        @\unlink($path);

        self::assertNull($transport->options[0]->progress);
        self::assertSame($default, $transport->options[1]->progress);
        self::assertSame($default, $transport->options[2]->progress);
        self::assertSame($override, $transport->options[3]->progress);
        self::assertSame($override, $transport->options[4]->progress);
    }

    public function testDeferredDownloads(): void
    {
        $directory = \sys_get_temp_dir();
        $first = $directory . '/tuxxedo-deferred-' . \bin2hex(\random_bytes(4));
        $second = $directory . '/tuxxedo-deferred-' . \bin2hex(\random_bytes(4));
        $progress = static fn (ClientProgressInterface $progress): bool => true;
        $transport = new ScriptedTransport(
            [
                self::response(content: 'one'),
                self::response(content: 'two'),
                self::response(404),
                HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/x'), HttpClientTimeout::IDLE),
            ],
        );

        $client = self::client($transport)->withMaxDownloadSize(100);
        $deferred = $client->deferred();

        try {
            $one = $deferred->download('/one', $first);
            $two = $deferred->download('/two', $second, maxSize: 10, progress: $progress);
            $missing = $deferred->download('/missing', $first . '.missing');
            $broken = $deferred->download('/broken', $first . '.broken');

            $client->wait($one, $two, $missing, $broken);

            self::assertSame('one', $one->result()->body->getRaw());
            self::assertSame('one', \file_get_contents($first));
            self::assertSame('two', \file_get_contents($second));
            self::assertSame(HttpClientFailure::STATUS, self::pendingFailure($missing));
            self::assertSame(HttpClientFailure::TIMEOUT, self::pendingFailure($broken));
            self::assertFileDoesNotExist($first . '.missing');
            self::assertSame([], \glob($directory . '/tuxxedo-deferred-*.part'));
            self::assertSame($first . '.', \substr((string) $transport->options[0]->sink, 0, \strlen($first) + 1));
            self::assertSame(100, $transport->options[0]->maxResponseSize);
            self::assertSame(10, $transport->options[1]->maxResponseSize);
            self::assertSame($progress, $transport->options[1]->progress);
            self::assertSame(0, $transport->options[0]->timeout->seconds);
        } finally {
            @\unlink($first);
            @\unlink($second);
        }
    }

    public function testDeferredDownloadsReportUnwritableTargets(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'data'),
            ],
        );

        $client = self::client($transport);
        $pending = $client->deferred()->download('/x', \sys_get_temp_dir() . '/missing-' . \bin2hex(\random_bytes(4)) . '/file');

        $client->wait($pending);

        self::assertSame(HttpClientFailure::REQUEST, self::pendingFailure($pending));
    }

    public function testMiddlewareWrapsEachAttemptInOrder(): void
    {
        $log = [];
        $outer = new CallbackMiddleware(
            static function (ClientRequestInterface $request) use (&$log): \Generator {
                $log[] = 'outer before';
                $outcome = yield $request->withHeader(new Header('X-Outer', '1'));
                $log[] = 'outer after ' . ($outcome instanceof ClientResponseInterface ? $outcome->statusCode : 'failure');
            },
        );

        $inner = new CallbackMiddleware(
            static function (ClientRequestInterface $request) use (&$log): \Generator {
                $log[] = 'inner before ' . self::header($request, 'X-Outer');
                yield $request->withHeader(new Header('X-Inner', '1'));
                $log[] = 'inner after';
            },
        );

        $transport = new ScriptedTransport(
            [
                self::response(503),
                self::response(content: 'ok'),
            ],
        );

        $response = self::client($transport)
            ->withRetry(self::fastRetry())
            ->withMiddleware($outer, $inner)
            ->get('/x');

        self::assertSame('ok', $response->body->getRaw());
        self::assertSame('1', self::header($transport->requests[1], 'X-Outer'));
        self::assertSame('1', self::header($transport->requests[1], 'X-Inner'));
        self::assertSame(
            [
                'outer before',
                'inner before 1',
                'inner after',
                'outer after 503',
                'outer before',
                'inner before 1',
                'inner after',
                'outer after 200',
            ],
            $log,
        );
    }

    public function testMiddlewareCanReplaceOrShortCircuitTheOutcome(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'upstream'),
            ],
        );

        $replace = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield $request;

                return ClientResponse::text('replaced');
            },
        );

        $shortCircuit = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield from [];

                return ClientResponse::text('cached');
            },
        );

        $client = self::client($transport);

        $replaced = $client->withMiddleware($replace)->get('/x');
        $cached = $client->withMiddleware($shortCircuit)->get('/x');

        self::assertSame('replaced', $replaced->body->getRaw());
        self::assertSame('cached', $cached->body->getRaw());
        self::assertCount(1, $transport->requests);
    }

    public function testMiddlewareSeesFailuresAndItsOwnExceptionsAreNotRetried(): void
    {
        $failures = [];
        $observer = new CallbackMiddleware(
            static function (ClientRequestInterface $request) use (&$failures): \Generator {
                $outcome = yield $request;

                if ($outcome instanceof HttpClientException) {
                    $failures[] = $outcome->failure;
                }
            },
        );

        $refusing = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield from [];

                throw HttpClientException::fromRateLimited($request);
            },
        );

        $transport = new ScriptedTransport(
            [
                HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/x'), HttpClientTimeout::IDLE),
            ],
        );

        $client = self::client($transport);
        $thrown = [];

        foreach (
            [
                $observer,
                $refusing,
            ] as $middleware
        ) {
            try {
                $client->withRetry(self::fastRetry(times: 3))->withMiddleware($middleware)->get('/x');
            } catch (HttpClientException $exception) {
                $thrown[] = $exception->failure;
            }
        }

        self::assertSame(
            [
                HttpClientFailure::TIMEOUT,
                HttpClientFailure::PROTOCOL,
                HttpClientFailure::PROTOCOL,
                HttpClientFailure::PROTOCOL,
            ],
            $failures,
        );

        self::assertSame(
            [
                HttpClientFailure::PROTOCOL,
                HttpClientFailure::RATE_LIMITED,
            ],
            $thrown,
        );

        self::assertCount(4, $transport->requests);
    }

    public function testMiddlewareDelaysAndMissingOutcomes(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'late'),
            ],
        );

        $delaying = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield Duration::fromMicroseconds(1);
                yield $request;
            },
        );

        $silent = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield from [];

                return null;
            },
        );

        $client = self::client($transport);

        $late = $client->withMiddleware($delaying)->get('/x');

        try {
            $client->withMiddleware($silent)->get('/x');

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::PROTOCOL, $exception->failure);
        }

        self::assertSame('late', $late->body->getRaw());
        self::assertCount(1, $delaying->seen);
    }

    public function testWithoutMiddlewareAndConstructorMiddleware(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(),
                self::response(),
            ],
        );

        $header = new CallbackMiddleware(
            static function (ClientRequestInterface $request): \Generator {
                yield $request->withHeader(new Header('X-Mw', '1'));
            },
        );

        $client = new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            baseUrl: 'https://api.example/v1',
            middleware: [
                $header,
            ],
            transport: $transport,
        );

        $client->get('/x');
        $client->withoutMiddleware()->get('/x');

        self::assertSame('1', self::header($transport->requests[0], 'X-Mw'));
        self::assertNull(self::header($transport->requests[1], 'X-Mw'));
    }

    public function testMiddlewareDelaysDoNotBlockOtherPendings(): void
    {
        $transport = new ScriptedTransport(
            [
                self::response(content: 'fast'),
                self::response(content: 'slow'),
            ],
        );

        $client = self::client($transport)->withMiddleware(
            new CallbackMiddleware(
                static function (ClientRequestInterface $request): \Generator {
                    if ($request->url->path === '/v1/slow') {
                        yield Duration::fromMilliseconds(20);
                    }

                    yield $request;
                },
            ),
        );

        $order = [];

        foreach (
            $client->each(
                [
                    'slow' => $client->deferred()->get('/slow'),
                    'fast' => $client->deferred()->get('/fast'),
                ],
            ) as $key => $pending
        ) {
            $order[] = $key;
        }

        self::assertSame(
            [
                'fast',
                'slow',
            ],
            $order,
        );
    }
}
