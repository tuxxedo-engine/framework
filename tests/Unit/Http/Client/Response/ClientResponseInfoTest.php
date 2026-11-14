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
use Tuxxedo\Http\Client\Response\ClientResponseHopInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInfo;
use Tuxxedo\Http\Client\Response\ClientResponseInfoInterface;
use Tuxxedo\Http\Client\Response\ClientResponseTimings;
use Tuxxedo\Http\Client\Response\ClientResponseTimingsInterface;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlInterface;

class ClientResponseInfoTest extends TestCase
{
    private static function makeInfo(): ClientResponseInfo
    {
        return new ClientResponseInfo(
            info: [
                'url' => 'https://api.example/new',
                'http_code' => 200,
                'primary_ip' => '192.0.2.2',
                'primary_port' => 443,
                'num_connects' => 0,
                'size_upload' => 10,
                'size_download' => 2048.0,
                'namelookup_time_us' => 0,
                'connect_time_us' => 0,
                'pretransfer_time_us' => 0,
                'starttransfer_time_us' => 300,
                'total_time_us' => 500,
            ],
            previousHops: [
                [
                    'url' => 'https://api.example/old',
                    'http_code' => 301,
                    'primary_ip' => '192.0.2.1',
                    'total_time_us' => 1_000,
                ],
                [
                    'url' => 'https://api.example/older',
                    'http_code' => 399,
                    'total_time_us' => 2_000,
                ],
            ],
        );
    }

    public function testValues(): void
    {
        $info = self::makeInfo();

        self::assertSame('/new', $info->effectiveUrl->path);
        self::assertSame('192.0.2.2', $info->primaryIp);
        self::assertSame(443, $info->primaryPort);
        self::assertTrue($info->reusedConnection);
        self::assertSame(10, $info->uploadedBytes);
        self::assertSame(2048, $info->downloadedBytes);
        self::assertSame(2, $info->redirectCount);
        self::assertTrue($info->redirected);
    }

    public function testTotalCoversEveryHop(): void
    {
        $total = self::makeInfo()->timings->total;

        self::assertSame(3_500_000, $total->seconds * 1_000_000_000 + $total->nanoseconds);
    }

    public function testHops(): void
    {
        $hops = self::makeInfo()->hops;

        self::assertCount(2, $hops);
        self::assertSame('/old', $hops[0]->url->path);
        self::assertSame(ResponseCode::MOVED_PERMANENTLY, $hops[0]->responseCode);
        self::assertSame(301, $hops[0]->statusCode);
        self::assertSame('192.0.2.1', $hops[0]->primaryIp);
        self::assertSame(ResponseCode::MULTIPLE_CHOICES, $hops[1]->responseCode);
        self::assertSame(399, $hops[1]->statusCode);
        self::assertNull($hops[1]->primaryIp);
    }

    public function testValuesAreMemoized(): void
    {
        $info = self::makeInfo();

        $effectiveUrl = $info->effectiveUrl;
        $timings = $info->timings;
        $hops = $info->hops;

        self::assertSame($effectiveUrl, $info->effectiveUrl);
        self::assertSame($timings, $info->timings);
        self::assertSame($hops, $info->hops);
    }

    public function testDefaultsForMissingValues(): void
    {
        $info = new ClientResponseInfo(
            info: [
                'url' => 'https://api.example/',
                'primary_port' => 'x',
                'num_connects' => 1,
            ],
        );

        self::assertNull($info->primaryIp);
        self::assertNull($info->primaryPort);
        self::assertFalse($info->reusedConnection);
        self::assertSame(0, $info->uploadedBytes);
        self::assertSame([], $info->hops);
        self::assertSame(0, $info->redirectCount);
        self::assertFalse($info->redirected);
    }

    public function testChainBuildsHopsFromPreviousTransfers(): void
    {
        $chained = ClientResponseInfo::chain(
            new ClientResponseInfo(
                [
                    'url' => 'https://api.example/new',
                    'total_time_us' => 500,
                ],
            ),
            [
                new ClientResponseInfo(
                    [
                        'url' => 'https://api.example/old',
                        'http_code' => 301,
                        'total_time_us' => 1_000,
                    ],
                ),
            ],
        );

        self::assertSame(1, $chained->redirectCount);
        self::assertSame('/old', $chained->hops[0]->url->path);
        self::assertSame(1_500_000, $chained->timings->total->nanoseconds);
    }

    public function testChainKeepsTheFinalInfoWhenItCannotChain(): void
    {
        $final = new ClientResponseInfo(
            [
                'url' => 'https://api.example/new',
            ],
        );

        $foreign = new class () implements ClientResponseInfoInterface {
            public UrlInterface $effectiveUrl;
            public ?string $primaryIp = null;
            public ?int $primaryPort = null;
            public bool $reusedConnection = false;
            public int $uploadedBytes = 0;
            public int $downloadedBytes = 0;
            public ClientResponseTimingsInterface $timings;

            /**
             * @var list<ClientResponseHopInterface>
             */
            public array $hops = [];

            public int $redirectCount = 0;
            public bool $redirected = false;

            public function __construct()
            {
                $this->effectiveUrl = Url::parse('https://foreign.example/');
                $this->timings = ClientResponseTimings::fromInfo([]);
            }
        };

        self::assertSame($final, ClientResponseInfo::chain($final, []));
        self::assertSame(
            $final,
            ClientResponseInfo::chain(
                $final,
                [
                    $foreign,
                ],
            ),
        );
        self::assertSame(
            $foreign,
            ClientResponseInfo::chain(
                $foreign,
                [
                    $final,
                ],
            ),
        );
    }
}
