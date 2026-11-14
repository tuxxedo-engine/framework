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

namespace Tuxxedo\Http\Client\Middleware;

use Tuxxedo\Http\Client\ClientMiddlewareInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\SystemClock;

class ServerRateLimitMiddleware implements ClientMiddlewareInterface
{
    private const int ABSOLUTE_AFTER = 315_360_000;

    private const array PAUSING_STATUSES = [
        429,
        503,
    ];

    private readonly ClockInterface $clock;

    /**
     * @var list<string>
     */
    private readonly array $remainingHeaders;

    /**
     * @var list<string>
     */
    private readonly array $resetHeaders;

    /**
     * @var array<string, float>
     */
    private array $pausedUntil = [];

    public function __construct(
        private readonly bool $wait = true,
        ?string $remainingHeader = null,
        ?string $resetHeader = null,
        ?ClockInterface $clock = null,
    ) {
        $this->remainingHeaders = $remainingHeader !== null
            ? [
                $remainingHeader,
            ]
            : [
                'RateLimit-Remaining',
                'X-RateLimit-Remaining',
            ];

        $this->resetHeaders = $resetHeader !== null
            ? [
                $resetHeader,
            ] : [
                'RateLimit-Reset',
                'X-RateLimit-Reset',
            ];

        $this->clock = $clock ?? new SystemClock();
    }

    public function handle(
        ClientRequestInterface $request,
    ): \Generator {
        $origin = self::origin($request);

        while (($wait = ($this->pausedUntil[$origin] ?? 0.0) - $this->now()) > 0) {
            if (!$this->wait) {
                throw HttpClientException::fromRateLimited($request);
            }

            yield Duration::fromMicroseconds((int) \ceil($wait * 1_000_000));
        }

        $outcome = yield $request;

        if ($outcome instanceof ClientResponseInterface) {
            $this->observe($origin, $outcome);
        }

        return $outcome;
    }

    private function observe(
        string $origin,
        ClientResponseInterface $response,
    ): void {
        $now = $this->now();
        $until = null;
        $remaining = self::first($response, $this->remainingHeaders);
        $reset = self::first($response, $this->resetHeaders);

        if ($remaining === '0' && $reset !== null && \preg_match('/^\d+$/', $reset) === 1) {
            $until = (int) $reset > self::ABSOLUTE_AFTER
                ? (float) $reset
                : $now + (int) $reset;
        }

        if (\in_array($response->statusCode, self::PAUSING_STATUSES, true) && $response->headers->has('Retry-After')) {
            $retryAfter = $this->retryAfter($response->headers->string('Retry-After'), $now);

            if ($retryAfter !== null) {
                $until = \max($until ?? 0.0, $retryAfter);
            }
        }

        if ($until !== null && $until > $now) {
            $this->pausedUntil[$origin] = \max($this->pausedUntil[$origin] ?? 0.0, $until);
        }
    }

    private function retryAfter(
        string $value,
        float $now,
    ): ?float {
        $value = \trim($value);

        if (\preg_match('/^\d+$/', $value) === 1) {
            return $now + (int) $value;
        }

        $date = \DateTimeImmutable::createFromFormat(
            format: 'D, d M Y H:i:s \G\M\T',
            datetime: $value,
            timezone: new \DateTimeZone('UTC'),
        );

        return $date !== false
            ? (float) $date->getTimestamp()
            : null;
    }

    private function now(): float
    {
        return (float) $this->clock->now()->toDateTime()->format('U.u');
    }

    /**
     * @param list<string> $names
     */
    private static function first(
        ClientResponseInterface $response,
        array $names,
    ): ?string {
        foreach ($names as $name) {
            if ($response->headers->has($name)) {
                return \trim($response->headers->string($name));
            }
        }

        return null;
    }

    private static function origin(
        ClientRequestInterface $request,
    ): string {
        $url = $request->url;

        return \strtolower($url->scheme . '://' . $url->host . ':' . ($url->port ?? ($url->scheme === 'http' ? 80 : 443)));
    }
}
