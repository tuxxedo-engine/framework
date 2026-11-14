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
use Tuxxedo\Http\Client\ClientResponse;
use Tuxxedo\Http\Client\ClientResponseInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\HttpClientFailure;
use Tuxxedo\Http\Client\HttpClientTimeout;
use Tuxxedo\Http\Client\Middleware\ServerRateLimitMiddleware;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\Instant;

class ServerRateLimitMiddlewareTest extends TestCase
{
    private const int NOW = 1_800_000_000;

    private static function clock(): ManualClock
    {
        return new ManualClock(Instant::fromUnixTimestamp(self::NOW));
    }

    /**
     * @param array<string, string> $headers
     */
    private static function response(
        array $headers,
        int $statusCode = 200,
    ): ClientResponse {
        $list = [];

        foreach ($headers as $name => $value) {
            $list[] = new Header($name, $value);
        }

        return new ClientResponse(
            headers: $list,
            statusCode: $statusCode,
        );
    }

    /**
     * @return list<int>
     */
    private static function delays(
        ServerRateLimitMiddleware $middleware,
        ManualClock $clock,
        ClientResponseInterface|HttpClientException|null $outcome = null,
        string $url = 'https://api.example/x',
    ): array {
        $request = new ClientRequest(Method::GET, $url);
        $generator = $middleware->handle($request);
        $delays = [];

        while ($generator->current() instanceof DurationInterface) {
            $delay = $generator->current();
            $delays[] = $delay->seconds;

            $clock->advance($delay->seconds * 1_000 + \intdiv($delay->nanoseconds, 1_000_000));
            $generator->send(null);
        }

        self::assertSame($request, $generator->current());

        $sent = $outcome ?? self::response([]);

        $generator->send($sent);

        self::assertSame($sent, $generator->getReturn());

        return $delays;
    }

    public function testPausesUntilAnAbsoluteGitHubStyleReset(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(clock: $clock);

        $first = self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'X-RateLimit-Remaining' => '0',
                    'X-RateLimit-Reset' => (string) (self::NOW + 30),
                ],
            ),
        );

        self::assertSame([], $first);
        self::assertSame(
            [
                30,
            ],
            self::delays($middleware, $clock),
        );

        self::assertSame([], self::delays($middleware, $clock));
    }

    public function testPausesForARelativeIetfStyleReset(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(clock: $clock);

        self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'RateLimit-Remaining' => '0',
                    'RateLimit-Reset' => '10',
                ],
            ),
        );

        self::assertSame(
            [
                10,
            ],
            self::delays($middleware, $clock),
        );
    }

    public function testRemainingBudgetAndOtherOriginsDoNotPause(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(clock: $clock);

        self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'RateLimit-Remaining' => '5',
                    'RateLimit-Reset' => '10',
                ],
            ),
        );

        self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'RateLimit-Remaining' => '0',
                    'RateLimit-Reset' => 'soon',
                ],
            ),
        );

        self::assertSame([], self::delays($middleware, $clock));

        self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'RateLimit-Remaining' => '0',
                    'RateLimit-Reset' => '10',
                ],
            ),
        );

        self::assertSame([], self::delays($middleware, $clock, url: 'https://other.example/'));
    }

    public function testRetryAfterOnTooManyRequestsPausesTheOrigin(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(clock: $clock);
        $date = \gmdate('D, d M Y H:i:s \G\M\T', self::NOW + 100);

        self::delays($middleware, $clock, self::retryAfter('5', 429));

        $seconds = self::delays($middleware, $clock);

        self::delays($middleware, $clock, self::retryAfter($date, 503));

        $waited = self::delays($middleware, $clock);

        self::delays($middleware, $clock, self::retryAfter('later', 503));
        self::delays($middleware, $clock, self::retryAfter('5', 500));

        self::assertSame(
            [
                5,
            ],
            $seconds,
        );

        self::assertSame(
            [
                95,
            ],
            $waited,
        );

        self::assertSame([], self::delays($middleware, $clock));
    }

    private static function retryAfter(
        string $value,
        int $statusCode,
    ): ClientResponse {
        return self::response(
            headers: [
                'Retry-After' => $value,
            ],
            statusCode: $statusCode,
        );
    }

    public function testCustomHeaderNamesAndFailures(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(
            remainingHeader: 'X-Quota-Left',
            resetHeader: 'X-Quota-Reset',
            clock: $clock,
        );

        $failure = HttpClientException::fromTimeout(new ClientRequest(Method::GET, '/x'), HttpClientTimeout::IDLE);

        self::delays($middleware, $clock, $failure);
        self::delays(
            $middleware,
            $clock,
            self::response(
                [
                    'X-Quota-Left' => '0',
                    'X-Quota-Reset' => '3',
                ],
            ),
        );

        self::assertSame(
            [
                3,
            ],
            self::delays($middleware, $clock),
        );
    }

    public function testFailFastWhilePaused(): void
    {
        $clock = self::clock();
        $middleware = new ServerRateLimitMiddleware(
            wait: false,
            clock: $clock,
        );

        self::delays($middleware, $clock, self::retryAfter('60', 429));

        try {
            $middleware->handle(new ClientRequest(Method::GET, 'https://api.example/x'))->current();

            self::fail('Expected HttpClientException was not thrown');
        } catch (HttpClientException $exception) {
            self::assertSame(HttpClientFailure::RATE_LIMITED, $exception->failure);
        }
    }

    public function testDefaultsToTheSystemClock(): void
    {
        self::assertInstanceOf(
            ClientRequest::class,
            new ServerRateLimitMiddleware()->handle(new ClientRequest(Method::GET, 'https://api.example/'))->current(),
        );
    }
}
