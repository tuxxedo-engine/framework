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

namespace Integration\Http\Client;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Support\Http\Client\LocalHttpServer;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\Body\MultipartBody;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\ClientCertificate;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\Proxy;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Client\Transport\CurlTransport;
use Tuxxedo\Http\Client\Transport\TransportOptions;
use Tuxxedo\Http\Client\Transport\TransportOptionsInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Temporal\Duration;

#[RequiresPhpExtension('curl')]
class CurlTransportIntegrationTest extends TestCase
{
    private static ?LocalHttpServer $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$server = LocalHttpServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    private static function url(
        string $path,
    ): string {
        self::assertNotNull(self::$server);

        return self::$server->url($path);
    }

    private static function transport(
        bool $persistentShare = true,
    ): CurlTransport {
        return new CurlTransport(
            serializerManager: SerializerManagerFactory::create(),
            persistentShare: $persistentShare,
        );
    }

    private static function send(
        ClientRequestInterface $request,
        ?TransportOptionsInterface $options = null,
        ?CurlTransport $transport = null,
    ): ClientResponseInterface|HttpClientException {
        $results = ($transport ?? self::transport())->send(
            [
                $request,
            ],
            $options ?? new TransportOptions(),
        );

        foreach ($results as $result) {
            return $result;
        }

        self::fail('The transport returned no result');
    }

    private static function response(
        ClientRequestInterface $request,
        ?TransportOptionsInterface $options = null,
    ): ClientResponseInterface {
        $result = self::send($request, $options);

        self::assertInstanceOf(ClientResponseInterface::class, $result);

        return $result;
    }

    private static function failure(
        ClientResponseInterface|HttpClientException $result,
    ): HttpClientException {
        self::assertInstanceOf(HttpClientException::class, $result);

        return $result;
    }

    public function testGetWithHeadersAndInfo(): void
    {
        $response = self::response(
            new ClientRequest(
                method: Method::GET,
                url: self::url('/echo?a=1'),
                headers: [
                    new Header('X-Test', '1'),
                ],
            ),
        );

        $raw = $response->body->getRaw();

        self::assertSame(200, $response->statusCode);
        self::assertTrue($response->body->isJson());
        self::assertStringContainsString('"method":"GET"', $raw);
        self::assertStringContainsString('"x-test":"1"', $raw);
        self::assertStringContainsString('"a":"1"', $raw);
        self::assertSame('127.0.0.1', $response->info->primaryIp);
        self::assertSame(self::$server?->port, $response->info->primaryPort);
        self::assertGreaterThan(0, $response->info->downloadedBytes);
        self::assertSame('/echo', $response->info->effectiveUrl->path);
    }

    public function testBodiesAreSent(): void
    {
        $file = ResourceStream::fromTemporary();

        $file->write('from a file');
        $file->rewind();

        $responses = [
            self::response(
                new ClientRequest(
                    method: Method::POST,
                    url: self::url('/echo'),
                    body: new JsonBody(
                        [
                            'a' => 1,
                        ],
                    ),
                ),
            ),
            self::response(
                new ClientRequest(
                    method: Method::PUT,
                    url: self::url('/echo'),
                    headers: [
                        new Header('Content-Type', 'text/plain'),
                    ],
                    body: new RawBody($file),
                ),
            ),
            self::response(
                new ClientRequest(
                    method: Method::PATCH,
                    url: self::url('/echo'),
                    body: new RawBody(
                        Stream::fromGenerator(
                            static function (): \Generator {
                                yield 'chunk';
                                yield 'ed';
                            },
                        ),
                    ),
                ),
            ),
            self::response(
                new ClientRequest(
                    method: Method::POST,
                    url: self::url('/echo'),
                    body: new MultipartBody(
                        [
                            'field' => 'value',
                        ],
                    ),
                ),
            ),
        ];

        self::assertStringContainsString('"contentType":"application\/json"', $responses[0]->body->getRaw());
        self::assertStringContainsString('"body":"{\"a\":1}"', $responses[0]->body->getRaw());
        self::assertStringContainsString('"contentType":"text\/plain"', $responses[1]->body->getRaw());
        self::assertStringContainsString('"body":"from a file"', $responses[1]->body->getRaw());
        self::assertStringContainsString('"method":"PATCH"', $responses[2]->body->getRaw());
        self::assertStringContainsString('"body":"chunked"', $responses[2]->body->getRaw());
        self::assertStringContainsString('multipart\/form-data', $responses[3]->body->getRaw());
    }

    public function testHeadHasNoBody(): void
    {
        $response = self::response(new ClientRequest(Method::HEAD, self::url('/big?size=100')));

        self::assertSame(200, $response->statusCode);
        self::assertSame('', $response->body->getRaw());
    }

    public function testConnectSendsTheAuthorityAsTarget(): void
    {
        $result = self::send(new ClientRequest(Method::CONNECT, self::url('/')));

        if ($result instanceof ClientResponseInterface) {
            self::assertGreaterThanOrEqual(100, $result->statusCode);
        } else {
            self::assertNotSame('', $result->getMessage());
        }
    }

    public function testCompressedResponsesAreDecoded(): void
    {
        self::assertSame('compressed', self::response(new ClientRequest(Method::GET, self::url('/gzip')))->body->getRaw());
    }

    public function testSendRunsRequestsConcurrently(): void
    {
        $results = [];

        foreach (
            self::transport(persistentShare: false)->send(
                [
                    'a' => new ClientRequest(Method::GET, self::url('/status/201')),
                    'b' => new ClientRequest(Method::GET, self::url('/status/202')),
                    'c' => new ClientRequest(Method::GET, self::url('/status/203')),
                ],
                new TransportOptions(),
                concurrency: 2,
            ) as $key => $result
        ) {
            self::assertInstanceOf(ClientResponseInterface::class, $result);

            $results[$key] = $result->statusCode;
        }

        \ksort($results);

        self::assertSame(
            [
                'a' => 201,
                'b' => 202,
                'c' => 203,
            ],
            $results,
        );
    }

    public function testAbandoningSendReleasesTheHandles(): void
    {
        $generator = self::transport()->send(
            [
                new ClientRequest(Method::GET, self::url('/status/200')),
                new ClientRequest(Method::GET, self::url('/slow?ms=200')),
            ],
            new TransportOptions(),
            concurrency: 2,
        );

        self::assertInstanceOf(ClientResponseInterface::class, $generator->current());

        unset($generator);

        self::assertSame(200, self::response(new ClientRequest(Method::GET, self::url('/status/200')))->statusCode);
    }

    /**
     * @return \Generator<array{0: string, 1: TransportOptionsInterface, 2: HttpClientFailure}>
     */
    public static function failureDataProvider(): \Generator
    {
        yield [
            '/slow?ms=2000',
            new TransportOptions(
                timeout: Duration::fromMilliseconds(300),
            ),
            HttpClientFailure::TIMEOUT,
        ];

        yield [
            '/big?size=2000',
            new TransportOptions(
                maxResponseSize: 1000,
            ),
            HttpClientFailure::SIZE,
        ];

        yield [
            '/echo',
            new TransportOptions(
                addressCheck: static fn (string $address): bool => false,
            ),
            HttpClientFailure::RULE,
        ];
    }

    #[DataProvider('failureDataProvider')]
    public function testFailures(
        string $path,
        TransportOptionsInterface $options,
        HttpClientFailure $failure,
    ): void {
        self::assertSame(
            $failure,
            self::failure(self::send(new ClientRequest(Method::GET, self::url($path)), $options))->failure,
        );
    }

    public function testTimeoutKinds(): void
    {
        $total = self::failure(
            self::send(
                new ClientRequest(Method::GET, self::url('/slow?ms=2000')),
                new TransportOptions(
                    timeout: Duration::fromMilliseconds(300),
                ),
            ),
        );

        $idle = self::failure(
            self::send(
                new ClientRequest(Method::GET, self::url('/stall?seconds=4')),
                new TransportOptions(
                    idleTimeout: Duration::fromSeconds(1),
                ),
            ),
        );

        self::assertStringContainsString(HttpClientTimeout::TOTAL->name, \strtoupper($total->getMessage()));
        self::assertSame(HttpClientFailure::TIMEOUT, $idle->failure);
        self::assertStringContainsString(HttpClientTimeout::IDLE->name, \strtoupper($idle->getMessage()));
    }

    public function testConnectionFailuresAreTransportErrors(): void
    {
        $result = self::send(new ClientRequest(Method::GET, 'http://127.0.0.1:' . LocalHttpServer::freePort() . '/'));

        self::assertSame(HttpClientFailure::TRANSPORT, self::failure($result)->failure);
    }

    /**
     * @return \Generator<array{0: HttpVersion}>
     */
    public static function httpVersionDataProvider(): \Generator
    {
        foreach (HttpVersion::cases() as $httpVersion) {
            yield [
                $httpVersion,
            ];
        }
    }

    #[DataProvider('httpVersionDataProvider')]
    public function testHttpVersions(
        HttpVersion $httpVersion,
    ): void {
        $result = self::send(
            new ClientRequest(
                method: Method::GET,
                url: self::url('/status/200'),
                httpVersion: $httpVersion,
            ),
        );

        if ($result instanceof ClientResponseInterface) {
            self::assertSame(200, $result->statusCode);
        } else {
            self::assertNotSame('', $result->getMessage());
        }
    }

    public function testProxyCertificateAndAuthoritySettingsAreApplied(): void
    {
        $response = self::response(
            new ClientRequest(Method::GET, self::url('/status/200')),
            new TransportOptions(
                proxy: new Proxy(
                    url: 'http://proxy.invalid:3128',
                    username: 'user',
                    password: 'secret',
                    noProxy: [
                        '127.0.0.1',
                    ],
                ),
                clientCertificate: new ClientCertificate(
                    certificate: __FILE__,
                    key: __FILE__,
                    passphrase: 'secret',
                ),
                certificateAuthority: __FILE__,
            ),
        );

        self::assertSame(200, $response->statusCode);
    }

    public function testStreamReturnsBeforeTheBodyArrives(): void
    {
        $result = self::transport()->stream(
            new ClientRequest(Method::GET, self::url('/chunks')),
            new TransportOptions(),
        );

        self::assertInstanceOf(ClientResponseInterface::class, $result);

        $stream = $result->body->getStream();
        $before = $stream->eof();
        $first = $stream->read();
        $rest = $stream->getContents();

        self::assertSame(200, $result->statusCode);
        self::assertFalse($before);
        self::assertNull($stream->getSize());
        self::assertSame('onetwothree', $first . $rest);
        self::assertTrue($stream->eof());
        self::assertNull($stream->read());

        unset($stream, $result);
    }

    public function testStreamFailures(): void
    {
        $refused = self::transport()->stream(
            new ClientRequest(Method::GET, 'http://127.0.0.1:' . LocalHttpServer::freePort() . '/'),
            new TransportOptions(),
        );

        $stalled = self::transport()->stream(
            new ClientRequest(Method::GET, self::url('/stall?seconds=4')),
            new TransportOptions(
                idleTimeout: Duration::fromSeconds(1),
            ),
        );

        self::assertSame(HttpClientFailure::TRANSPORT, self::failure($refused)->failure);
        self::assertInstanceOf(ClientResponseInterface::class, $stalled);

        try {
            $stalled->body->getRaw();

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::TIMEOUT, $exception->failure);
        }
    }
}
