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

use Tuxxedo\Http\Client\Cache\CacheEntry;
use Tuxxedo\Http\Client\Cache\CacheEntryInterface;
use Tuxxedo\Http\Client\Cache\CacheStoreInterface;
use Tuxxedo\Http\Client\ClientMiddlewareInterface;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Method;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\SystemClock;

class CacheMiddleware implements ClientMiddlewareInterface
{
    private const array CACHEABLE_STATUSES = [
        200,
        203,
        301,
        308,
        404,
        410,
    ];

    private readonly DurationInterface $staleTtl;
    private readonly ClockInterface $clock;

    public function __construct(
        private readonly CacheStoreInterface $store,
        private readonly ?DurationInterface $defaultTtl = null,
        ?DurationInterface $staleTtl = null,
        private readonly int $maxEntrySize = 1024 * 1024,
        ?ClockInterface $clock = null,
    ) {
        $this->staleTtl = $staleTtl ?? Duration::fromHours(24);
        $this->clock = $clock ?? new SystemClock();
    }

    public function handle(
        ClientRequestInterface $request,
    ): \Generator {
        $directives = self::directives(CacheEntry::requestHeader($request, 'Cache-Control'));

        if (($request->method !== Method::GET && $request->method !== Method::HEAD) || isset($directives['no-store'])) {
            return yield $request;
        }

        $key = self::key($request);
        $entry = $this->store->get($key);

        if ($entry !== null && !$entry->matches($request)) {
            $entry = null;
        }

        if ($entry !== null && !isset($directives['no-cache']) && $entry->freshUntil > $this->now()) {
            return $entry->toResponse($request);
        }

        $outcome = yield self::conditional($request, $entry);

        if (!$outcome instanceof ClientResponseInterface) {
            return $outcome;
        }

        if ($entry !== null && $outcome->statusCode === 304) {
            $refreshed = $this->refresh($entry, $outcome);

            $this->store->set($key, $refreshed, $this->ttl($refreshed));

            return $refreshed->toResponse($request);
        }

        $this->remember($key, $request, $outcome);

        return $outcome;
    }

    private function remember(
        string $key,
        ClientRequestInterface $request,
        ClientResponseInterface $response,
    ): void {
        $directives = self::directives(self::responseHeader($response, 'Cache-Control'));

        if (isset($directives['no-store'])) {
            $this->store->delete($key);

            return;
        }

        if (!\in_array($response->statusCode, self::CACHEABLE_STATUSES, true) || !$response->buffered) {
            return;
        }

        $vary = [];

        foreach (\explode(',', self::responseHeader($response, 'Vary')) as $name) {
            $name = \strtolower(\trim($name));

            if ($name === '*') {
                return;
            }

            if ($name !== '') {
                $vary[$name] = CacheEntry::requestHeader($request, $name);
            }
        }

        $body = $response->body->getRaw();

        if (\strlen($body) > $this->maxEntrySize) {
            return;
        }

        $headers = [];

        foreach ($response->headers->all() as $header) {
            $headers[] = [
                $header->name,
                $header->value,
            ];
        }

        $freshness = isset($directives['no-cache'])
            ? 0.0
            : $this->freshness($headers, $directives);

        $entry = new CacheEntry(
            statusCode: $response->statusCode,
            httpVersion: $response->httpVersion,
            headers: $headers,
            body: $body,
            freshUntil: $this->now() + ($freshness ?? 0.0),
            vary: $vary,
        );

        if ($freshness === null && $entry->etag === null && $entry->lastModified === null) {
            return;
        }

        $this->store->set($key, $entry, $this->ttl($entry));
    }

    private function refresh(
        CacheEntryInterface $entry,
        ClientResponseInterface $notModified,
    ): CacheEntryInterface {
        $updates = [];

        foreach ($notModified->headers->all() as $header) {
            $updates[\strtolower($header->name)] = [
                $header->name,
                $header->value,
            ];
        }

        $headers = [];

        foreach ($entry->headers as $header) {
            $headers[] = $updates[\strtolower($header[0])] ?? $header;

            unset($updates[\strtolower($header[0])]);
        }

        $headers = [
            ...$headers,
            ...\array_values($updates),
        ];

        $directives = [];

        foreach ($headers as [$name, $value]) {
            if (\strcasecmp($name, 'Cache-Control') === 0) {
                $directives = self::directives($value);
            }
        }

        $freshness = isset($directives['no-cache'])
            ? 0.0
            : $this->freshness($headers, $directives);

        return new CacheEntry(
            statusCode: $entry->statusCode,
            httpVersion: $entry->httpVersion,
            headers: $headers,
            body: $entry->body,
            freshUntil: $this->now() + ($freshness ?? 0.0),
            vary: $entry->vary,
        );
    }

    /**
     * @param list<array{0: string, 1: string}> $headers
     * @param array<string, string> $directives
     */
    private function freshness(
        array $headers,
        array $directives,
    ): ?float {
        $values = [];

        foreach ($headers as [$name, $value]) {
            $values[\strtolower($name)] = $value;
        }

        $age = \trim($values['age'] ?? '');
        $age = \preg_match('/^\d+$/', $age) === 1
            ? (int) $age
            : 0;


        if (\preg_match('/^\d+$/', $directives['max-age'] ?? '') === 1) {
            return (float) \max(0, (int) $directives['max-age'] - $age);
        }

        $expires = self::date($values['expires'] ?? '');

        if ($expires !== null) {
            return \max(0.0, $expires - (self::date($values['date'] ?? '') ?? $this->now()));
        }

        if ($this->defaultTtl === null) {
            return null;
        }

        return $this->defaultTtl->seconds + $this->defaultTtl->nanoseconds / 1_000_000_000;
    }

    private function ttl(
        CacheEntryInterface $entry,
    ): DurationInterface {
        $fresh = (int) \ceil(\max(0.0, $entry->freshUntil - $this->now()));

        if ($entry->etag === null && $entry->lastModified === null) {
            return Duration::fromSeconds(\max(1, $fresh));
        }

        return Duration::fromSeconds(\max($fresh, $this->staleTtl->seconds));
    }

    private function now(): float
    {
        return (float) $this->clock->now()->toDateTime()->format('U.u');
    }

    private static function conditional(
        ClientRequestInterface $request,
        ?CacheEntryInterface $entry,
    ): ClientRequestInterface {
        if ($entry?->etag !== null && !$request->hasHeader('If-None-Match')) {
            $request = $request->withHeader(new Header('If-None-Match', $entry->etag));
        }

        if ($entry?->lastModified !== null && !$request->hasHeader('If-Modified-Since')) {
            $request = $request->withHeader(new Header('If-Modified-Since', $entry->lastModified));
        }

        return $request;
    }

    private static function key(
        ClientRequestInterface $request,
    ): string {
        return \hash(
            'sha256',
            \join(
                "\n",
                [
                    $request->method->name,
                    (string) $request->url,
                    CacheEntry::requestHeader($request, 'Authorization'),
                    CacheEntry::requestHeader($request, 'Cookie'),
                ],
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function directives(
        string $value,
    ): array {
        $directives = [];

        foreach (\explode(',', $value) as $directive) {
            [$name, $argument] = \array_pad(\explode('=', \trim($directive), 2), 2, '');

            if ($name !== '') {
                $directives[\strtolower($name)] = \trim($argument, " \t\"");
            }
        }

        return $directives;
    }

    private static function responseHeader(
        ClientResponseInterface $response,
        string $name,
    ): string {
        return $response->headers->has($name)
            ? $response->headers->string($name)
            : '';
    }

    private static function date(
        string $value,
    ): ?float {
        $date = \DateTimeImmutable::createFromFormat(
            format: 'D, d M Y H:i:s \G\M\T',
            datetime: \trim($value),
            timezone: new \DateTimeZone('UTC'),
        );

        return $date !== false
            ? (float) $date->getTimestamp()
            : null;
    }
}
