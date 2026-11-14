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

namespace Unit\Http\Client\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Cache\CacheEntry;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;

class CacheEntryTest extends TestCase
{
    private static function entry(): CacheEntry
    {
        return new CacheEntry(
            statusCode: 200,
            httpVersion: HttpVersion::V2_0,
            headers: [
                [
                    'ETag',
                    '"v1"',
                ],
                [
                    'last-modified',
                    'Tue, 01 Jan 2030 00:00:00 GMT',
                ],
            ],
            body: "\x00binary",
            freshUntil: 1_000.5,
            vary: [
                'accept-language' => 'da',
            ],
        );
    }

    public function testRoundTripsThroughAnArray(): void
    {
        $entry = self::entry();
        $restored = CacheEntry::tryFromArray($entry->toArray());

        self::assertNotNull($restored);
        self::assertSame($entry->toArray(), $restored->toArray());
        self::assertSame('"v1"', $restored->etag);
        self::assertSame('Tue, 01 Jan 2030 00:00:00 GMT', $restored->lastModified);
    }

    public function testMatchesVariesAndBuildsResponses(): void
    {
        $entry = self::entry();
        $danish = new ClientRequest(
            method: Method::GET,
            url: 'https://api.example/x',
            headers: [
                new Header('Accept-Language', 'da'),
            ],
        );
        $response = $entry->toResponse($danish);

        self::assertTrue($entry->matches($danish));
        self::assertFalse($entry->matches(new ClientRequest(Method::GET, 'https://api.example/x')));
        self::assertSame("\x00binary", $response->body->getRaw());
        self::assertSame('"v1"', $response->headers->string('ETag'));
        self::assertSame(HttpVersion::V2_0, $response->httpVersion);
        self::assertSame('/x', $response->info->effectiveUrl->path);
    }

    /**
     * @return \Generator<array{0: array<mixed>}>
     */
    public static function invalidDataProvider(): \Generator
    {
        $valid = self::entry()->toArray();

        yield [
            [],
        ];

        yield [
            [
                ...$valid,
                'headers' => 'nope',
            ],
        ];

        yield [
            [
                ...$valid,
                'httpVersion' => 'HTTP/9',
            ],
        ];

        yield [
            [
                ...$valid,
                'body' => '***',
            ],
        ];

        yield [
            [
                ...$valid,
                'freshUntil' => 'soon',
            ],
        ];
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProvider('invalidDataProvider')]
    public function testInvalidDataIsRejected(
        array $data,
    ): void {
        self::assertNull(CacheEntry::tryFromArray($data));
    }

    public function testMalformedHeaderAndVaryItemsAreSkipped(): void
    {
        $entry = CacheEntry::tryFromArray(
            [
                ...self::entry()->toArray(),
                'headers' => [
                    'nope',
                    [
                        'X-A',
                        '1',
                    ],
                ],
                'vary' => [
                    'accept' => 1,
                ],
            ],
        );

        self::assertNotNull($entry);
        self::assertSame(
            [
                [
                    'X-A',
                    '1',
                ],
            ],
            $entry->headers,
        );
        self::assertSame([], $entry->vary);
    }
}
