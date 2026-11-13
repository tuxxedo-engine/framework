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

namespace Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\StatusLine;

class StatusLineTest extends TestCase
{
    public function testConstructorResolvesTheResponseCode(): void
    {
        $statusLine = new StatusLine(
            httpVersion: HttpVersion::V1_1,
            statusCode: 404,
            reasonPhrase: 'Not Found',
        );

        self::assertSame(HttpVersion::V1_1, $statusLine->httpVersion);
        self::assertSame(404, $statusLine->statusCode);
        self::assertSame('Not Found', $statusLine->reasonPhrase);
        self::assertSame(ResponseCode::NOT_FOUND, $statusLine->responseCode);
    }

    /**
     * @return \Generator<array{0: string, 1: HttpVersion, 2: int, 3: ResponseCode, 4: string}>
     */
    public static function parseDataProvider(): \Generator
    {
        yield [
            "HTTP/1.1 200 OK\r\n",
            HttpVersion::V1_1,
            200,
            ResponseCode::OK,
            'OK',
        ];

        yield [
            'HTTP/1.0 301 Moved Permanently',
            HttpVersion::V1_0,
            301,
            ResponseCode::MOVED_PERMANENTLY,
            'Moved Permanently',
        ];

        yield [
            'HTTP/2 204',
            HttpVersion::V2_0,
            204,
            ResponseCode::NO_CONTENT,
            '',
        ];

        yield [
            'HTTP/3 200',
            HttpVersion::V3_0,
            200,
            ResponseCode::OK,
            '',
        ];

        yield [
            'HTTP/1.1 509 Bandwidth Limit Exceeded',
            HttpVersion::V1_1,
            509,
            ResponseCode::INTERNAL_SERVER_ERROR,
            'Bandwidth Limit Exceeded',
        ];
    }

    #[DataProvider('parseDataProvider')]
    public function testParse(
        string $line,
        HttpVersion $httpVersion,
        int $statusCode,
        ResponseCode $responseCode,
        string $reasonPhrase,
    ): void {
        $statusLine = StatusLine::parse($line);

        self::assertSame($httpVersion, $statusLine->httpVersion);
        self::assertSame($statusCode, $statusLine->statusCode);
        self::assertSame($responseCode, $statusLine->responseCode);
        self::assertSame($reasonPhrase, $statusLine->reasonPhrase);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function invalidLineDataProvider(): \Generator
    {
        yield [
            'garbage',
        ];

        yield [
            'HTTP/1.1 99 Too Short',
        ];

        yield [
            'HTTP/4.0 200 OK',
        ];

        yield [
            'HTTP/1.1 600 Out Of Range',
        ];
    }

    #[DataProvider('invalidLineDataProvider')]
    public function testParseThrowsForInvalidLines(
        string $line,
    ): void {
        $this->expectException(HttpException::class);

        StatusLine::parse($line);
    }
}
