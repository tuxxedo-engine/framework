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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Response\ClientResponseHop;
use Tuxxedo\Http\Client\Response\ClientResponseTimings;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Url\Url;

class ClientResponseHopTest extends TestCase
{
    public function testConstructor(): void
    {
        $url = Url::parse('https://api.example/old');
        $timings = ClientResponseTimings::fromInfo([]);
        $hop = new ClientResponseHop(
            url: $url,
            responseCode: ResponseCode::MOVED_PERMANENTLY,
            statusCode: 301,
            primaryIp: '192.0.2.1',
            timings: $timings,
        );

        self::assertSame($url, $hop->url);
        self::assertSame(ResponseCode::MOVED_PERMANENTLY, $hop->responseCode);
        self::assertSame(301, $hop->statusCode);
        self::assertSame('192.0.2.1', $hop->primaryIp);
        self::assertSame($timings, $hop->timings);
    }
}
