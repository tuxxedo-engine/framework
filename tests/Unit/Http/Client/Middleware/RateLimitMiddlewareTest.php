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

use PHPUnit\Framework\TestCase;
use Support\Temporal\ManualClock;
use Tuxxedo\Http\Client\ClientRequest;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\Middleware\RateLimitMiddleware;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\Instant;

class RateLimitMiddlewareTest extends TestCase
{
    private static function clock(): ManualClock
    {
        return new ManualClock(Instant::fromUnixTimestamp(1_000));
    }

    private static function first(
        RateLimitMiddleware $middleware,
        string $url = 'https://api.example/x',
    ): mixed {
        return $middleware->handle(new ClientRequest(Method::GET, $url))->current();
    }

    public function testBurstThenWait(): void
    {
        $clock = self::clock();
        $middleware = new RateLimitMiddleware(
            requests: 2,
            per: Duration::fromSeconds(1),
            clock: $clock,
        );

        $request = new ClientRequest(Method::GET, 'https://api.example/x');
        $burst = [
            self::first($middleware),
            self::first($middleware),
        ];

        $third = $middleware->handle($request);
        $wait = $third->current();

        self::assertInstanceOf(ClientRequest::class, $burst[0]);
        self::assertInstanceOf(ClientRequest::class, $burst[1]);
        self::assertInstanceOf(DurationInterface::class, $wait);
        self::assertSame(0, $wait->seconds);
        self::assertSame(500_000_000, $wait->nanoseconds);

        $clock->advance(500);
        $third->send(null);

        self::assertSame($request, $third->current());
    }

    public function testBucketsArePerOriginUnlessShared(): void
    {
        $clock = self::clock();
        $perOrigin = new RateLimitMiddleware(
            requests: 1,
            per: Duration::fromSeconds(1),
            clock: $clock,
        );

        $shared = new RateLimitMiddleware(
            requests: 1,
            per: Duration::fromSeconds(1),
            perOrigin: false,
            clock: $clock,
        );

        self::first($perOrigin, 'https://a.example/');
        self::first($shared, 'https://a.example/');

        self::assertInstanceOf(ClientRequest::class, self::first($perOrigin, 'http://b.example/'));
        self::assertInstanceOf(DurationInterface::class, self::first($shared, 'http://b.example/'));
    }

    public function testFailFastThrows(): void
    {
        $middleware = new RateLimitMiddleware(
            requests: 1,
            per: Duration::fromMinutes(1),
            wait: false,
            clock: self::clock(),
        );

        self::first($middleware);

        try {
            self::first($middleware);

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RATE_LIMITED, $exception->failure);
        }
    }

    public function testDefaultsToTheSystemClock(): void
    {
        self::assertInstanceOf(
            ClientRequest::class,
            self::first(new RateLimitMiddleware(1, Duration::fromSeconds(1))),
        );
    }
}
