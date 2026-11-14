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
use Tuxxedo\Http\Client\ClientRequestInterface;
use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\SystemClock;

class RateLimitMiddleware implements ClientMiddlewareInterface
{
    private readonly float $rate;
    private readonly ClockInterface $clock;

    /**
     * @var array<string, array{0: float, 1: float}>
     */
    private array $buckets = [];

    public function __construct(
        private readonly int $requests,
        DurationInterface $per,
        private readonly bool $perOrigin = true,
        private readonly bool $wait = true,
        ?ClockInterface $clock = null,
    ) {
        $this->rate = \max(1, $requests) / \max(0.000001, $per->seconds + $per->nanoseconds / 1_000_000_000);
        $this->clock = $clock ?? new SystemClock();
    }

    public function handle(
        ClientRequestInterface $request,
    ): \Generator {
        $key = $this->perOrigin
            ? self::origin($request)
            : '';

        while (($wait = $this->reserve($key)) !== null) {
            if (!$this->wait) {
                throw HttpClientException::fromRateLimited($request);
            }

            yield $wait;
        }

        return yield $request;
    }

    private function reserve(
        string $key,
    ): ?DurationInterface {
        $now = (float) $this->clock->now()->toDateTime()->format('U.u');
        $capacity = (float) \max(1, $this->requests);

        $tokens = $this->buckets[$key][0] ?? $capacity;
        $updated = $this->buckets[$key][1] ?? $now;

        $tokens = \min($capacity, $tokens + \max(0.0, $now - $updated) * $this->rate);

        if ($tokens >= 1.0) {
            $this->buckets[$key] = [
                $tokens - 1.0,
                $now,
            ];

            return null;
        }

        $this->buckets[$key] = [
            $tokens,
            $now,
        ];

        return Duration::fromMicroseconds(\max(1, (int) \ceil((1.0 - $tokens) / $this->rate * 1_000_000)));
    }

    private static function origin(
        ClientRequestInterface $request,
    ): string {
        $url = $request->url;

        return \strtolower($url->scheme . '://' . $url->host . ':' . ($url->port ?? ($url->scheme === 'http' ? 80 : 443)));
    }
}
