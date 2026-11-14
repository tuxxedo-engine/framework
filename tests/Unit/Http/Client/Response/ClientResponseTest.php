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

namespace Unit\Http\Client\Response;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Client\Response\ClientResponse;
use Tuxxedo\Http\Client\Response\ClientResponseInfo;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HeaderInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Temporal\Instant;

class ClientResponseTest extends TestCase
{
    public function testDefaults(): void
    {
        $response = new ClientResponse();

        self::assertSame(200, $response->statusCode);
        self::assertSame(ResponseCode::OK, $response->responseCode);
        self::assertSame(HttpVersion::V1_1, $response->httpVersion);
        self::assertSame([], $response->headers->all());
        self::assertSame('', $response->body->getRaw());
        self::assertSame([], $response->cookies);
        self::assertFalse($response->info->redirected);
    }

    public function testUnknownStatusCodesResolveToTheirClass(): void
    {
        $response = new ClientResponse(statusCode: 509);

        self::assertSame(509, $response->statusCode);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $response->responseCode);
    }

    public function testHeadersAndBody(): void
    {
        $response = new ClientResponse(
            content: '{"data":{"name":"John","age":30}}',
            headers: [
                new Header('content-type', 'application/json'),
                new Header('X-RateLimit-Remaining', '42'),
            ],
            httpVersion: HttpVersion::V2_0,
            serializerManager: SerializerManagerFactory::create(),
        );

        self::assertSame(HttpVersion::V2_0, $response->httpVersion);
        self::assertSame(42, $response->headers->int('x-ratelimit-remaining'));
        self::assertTrue($response->body->isJson());
        self::assertSame('John', $response->body->jsonMap(BodyContextFixture::class, key: 'data')->name);
    }

    public function testCookiesAreParsedAgainstTheEffectiveUrl(): void
    {
        $response = new ClientResponse(
            headers: [
                new Header('Set-Cookie', 'session=abc; Max-Age=60; Domain=example.com'),
                new Header('Set-Cookie', 'other=1; Domain=evil.example'),
                new Header('Set-Cookie', 'theme=dark'),
            ],
            info: new ClientResponseInfo(
                [
                    'url' => 'https://www.example.com/account/settings',
                ],
            ),
            receivedAt: Instant::fromUnixTimestamp(1_700_000_000),
        );

        $cookies = $response->cookies;

        self::assertCount(2, $cookies);
        self::assertSame('session', $cookies[0]->name);
        self::assertSame(1_700_000_060, $cookies[0]->expires);
        self::assertSame('example.com', $cookies[0]->domain);
        self::assertSame('theme', $cookies[1]->name);
        self::assertSame('/account', $cookies[1]->path);
        self::assertSame($cookies, $response->cookies);
    }

    public function testJsonFactory(): void
    {
        $response = ClientResponse::json(
            data: [
                'id' => 1,
            ],
            statusCode: 201,
            headers: [
                new Header('X-A', '1'),
            ],
        );

        self::assertSame(201, $response->statusCode);
        self::assertSame('application/json', $response->headers->string('Content-Type'));
        self::assertSame('1', $response->headers->string('X-A'));
        self::assertSame('{"id":1}', $response->body->getRaw());
    }

    public function testTextHtmlAndEmptyFactories(): void
    {
        $text = ClientResponse::text('hello', 404);
        $html = ClientResponse::html('<p>hi</p>');
        $empty = ClientResponse::empty();

        self::assertSame('text/plain; charset=utf-8', $text->headers->string('Content-Type'));
        self::assertSame(404, $text->statusCode);
        self::assertSame('hello', $text->body->getRaw());
        self::assertSame('text/html; charset=utf-8', $html->headers->string('Content-Type'));
        self::assertSame('<p>hi</p>', $html->body->getRaw());
        self::assertSame(204, $empty->statusCode);
        self::assertSame('', $empty->body->getRaw());
    }

    public function testToResponseDropsHopByHopAndUpstreamOnlyHeaders(): void
    {
        $response = new ClientResponse(
            content: 'report',
            headers: [
                new Header('Content-Type', 'text/csv'),
                new Header('Content-Encoding', 'gzip'),
                new Header('Content-Length', '120'),
                new Header('Transfer-Encoding', 'chunked'),
                new Header('Connection', 'keep-alive'),
                new Header('Set-Cookie', 'session=abc'),
                new Header('X-Report-Id', '7'),
            ],
            statusCode: 509,
            httpVersion: HttpVersion::V2_0,
        )->toResponse(new Container());

        self::assertSame('report', $response->body);
        self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $response->responseCode);
        self::assertSame(HttpVersion::V2_0, $response->httpVersion);
        self::assertSame(
            [
                'Content-Type',
                'X-Report-Id',
            ],
            \array_map(static fn (HeaderInterface $header): string => $header->name, $response->headers),
        );
    }

    public function testToResponseKeepsStreamBodies(): void
    {
        $stream = ResourceStream::fromTemporary();

        self::assertSame($stream, new ClientResponse($stream)->toResponse(new Container())->body);
    }
}
