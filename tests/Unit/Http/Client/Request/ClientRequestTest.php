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

namespace Unit\Http\Client\Request;

use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\JsonBody;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Client\Request\ClientRequest;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Stream\StreamInterface;
use Tuxxedo\Http\Url\Url;

class ClientRequestTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo-client-request-');

        if ($path === false) {
            self::fail('Failed to allocate temporary file path');
        }

        \file_put_contents($path, 'payload');

        $this->path = $path;
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function testConstructorParsesStrings(): void
    {
        $request = new ClientRequest('post', '/users?a=1');

        self::assertSame(Method::POST, $request->method);
        self::assertSame('/users', $request->url->path);
        self::assertSame('a=1', $request->url->query);
        self::assertSame([], $request->headers);
        self::assertNull($request->body);
        self::assertSame([], $request->query);
        self::assertNull($request->httpVersion);
    }

    public function testConstructorKeepsValueObjects(): void
    {
        $url = Url::parse('https://api.example/users');
        $body = new JsonBody([]);
        $request = new ClientRequest(
            method: Method::PUT,
            url: $url,
            headers: [
                new Header('X-A', '1'),
            ],
            body: $body,
            query: [
                'page' => 2,
            ],
            httpVersion: HttpVersion::V2_0,
        );

        self::assertSame(Method::PUT, $request->method);
        self::assertSame($url, $request->url);
        self::assertSame($body, $request->body);
        self::assertSame(
            [
                'page' => 2,
            ],
            $request->query,
        );

        self::assertSame(HttpVersion::V2_0, $request->httpVersion);
    }

    public function testWithMethodAndUrl(): void
    {
        $request = new ClientRequest(Method::GET, '/a');
        $changed = $request
            ->withMethod('delete')
            ->withUrl('/b');

        self::assertSame(Method::GET, $request->method);
        self::assertSame(Method::DELETE, $changed->method);
        self::assertSame('/b', $changed->url->path);
        self::assertSame(Method::PATCH, $changed->withMethod(Method::PATCH)->method);

        $url = Url::parse('/c');

        self::assertSame($url, $changed->withUrl($url)->url);
    }

    public function testHeaders(): void
    {
        $request = new ClientRequest(Method::GET, '/')
            ->withHeader(new Header('Accept', 'text/html'))
            ->withHeader(new Header('accept', 'application/json'))
            ->withHeaders(
                [
                    new Header('X-A', '1'),
                    new Header('X-B', '2'),
                ],
            );

        self::assertTrue($request->hasHeader('ACCEPT'));
        self::assertFalse($request->hasHeader('X-Missing'));
        self::assertCount(4, $request->headers);

        $replaced = $request->withHeader(new Header('Accept', 'text/csv'), replace: true);

        self::assertCount(3, $replaced->headers);
        self::assertSame('text/csv', $replaced->headers[2]->value);

        $without = $request->withoutHeader('accept');

        self::assertCount(2, $without->headers);
        self::assertFalse($without->hasHeader('Accept'));
    }

    public function testWithBodyQueryAndHttpVersion(): void
    {
        $body = new RawBody('x');
        $request = new ClientRequest(
            method: Method::POST,
            url: '/',
            query: [
                'a' => 1,
                'b' => 2,
            ],
        )
            ->withBody($body)
            ->withHttpVersion(HttpVersion::V1_1);

        self::assertSame($body, $request->body);
        self::assertSame(HttpVersion::V1_1, $request->httpVersion);
        self::assertNull($request->withBody(null)->body);
        self::assertSame(
            [
                'a' => 1,
                'b' => 3,
                'c' => 4,
            ],
            $request->withQuery(
                [
                    'b' => 3,
                    'c' => 4,
                ],
            )->query,
        );

        self::assertSame(
            [
                'c' => 4,
            ],
            $request->withQuery(
                [
                    'c' => 4,
                ],
                replace: true,
            )->query,
        );
    }

    public function testFromRequestForwardsMethodHeadersAndBody(): void
    {
        $request = $this->incomingRequest(
            headers: [
                'Host' => 'shop.example',
                'Connection' => 'keep-alive',
                'Content-Length' => '7',
                'Content-Type' => 'application/json',
                'X-Request-Id' => 'abc',
            ],
            cookies: [
                'session' => 'secret',
            ],
        );

        $forwarded = ClientRequest::fromRequest($request, 'https://upstream.example/orders');

        self::assertSame(Method::POST, $forwarded->method);
        self::assertSame('upstream.example', $forwarded->url->host);
        self::assertSame(
            [
                'Content-Type',
                'X-Request-Id',
            ],
            \array_map(static fn (HeaderInterface $header): string => $header->name, $forwarded->headers),
        );

        self::assertFalse($forwarded->hasHeader('Cookie'));

        $body = $forwarded->body;

        self::assertInstanceOf(RawBody::class, $body);
        self::assertSame('application/json', $body->contentType);
        self::assertInstanceOf(StreamInterface::class, $body->body);
        self::assertSame('payload', $body->body->getContents());
    }

    public function testFromRequestForwardsCookiesOnlyWhenAsked(): void
    {
        $forwarded = ClientRequest::fromRequest(
            request: $this->incomingRequest(
                cookies: [
                    'session' => 'secret',
                    'theme' => 'dark',
                ],
            ),
            url: 'https://upstream.example/',
            forwardCookies: true,
        );

        self::assertCount(1, $forwarded->headers);
        self::assertSame('session=secret; theme=dark', $forwarded->headers[0]->value);
        self::assertSame('application/octet-stream', $forwarded->body?->contentType);
    }

    public function testFromRequestWithoutCookiesAddsNoCookieHeader(): void
    {
        $forwarded = ClientRequest::fromRequest(
            request: $this->incomingRequest(),
            url: 'https://upstream.example/',
            forwardCookies: true,
        );

        self::assertSame([], $forwarded->headers);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    private function incomingRequest(
        array $headers = [],
        array $cookies = [],
    ): Request {
        $serializerManager = SerializerManagerFactory::create();

        return new Request(
            serializerManager: $serializerManager,
            headers: new StubHeaderContext($headers),
            cookies: new StubInputContext($cookies),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new EnvironmentBodyContext(
                serializerManager: $serializerManager,
                streamInputSource: $this->path,
            ),
            method: Method::POST,
        );
    }
}
