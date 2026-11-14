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

use PHPUnit\Framework\TestCase;
use Support\Cache\InMemoryCacheDriver;
use Tuxxedo\Cache\Cache;
use Tuxxedo\Http\Client\Cache\CacheEntry;
use Tuxxedo\Http\Client\Cache\CacheStore;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Serializer\ArraySerializer;
use Tuxxedo\Temporal\Duration;

class CacheStoreTest extends TestCase
{
    public function testStoresEntriesInAnEngineCache(): void
    {
        $cache = new Cache(
            driver: new InMemoryCacheDriver(),
            serializer: new ArraySerializer(
                skipValidation: true,
            ),
        );
        $store = new CacheStore($cache);
        $entry = new CacheEntry(
            statusCode: 200,
            httpVersion: HttpVersion::V1_1,
            headers: [
                [
                    'ETag',
                    '"v1"',
                ],
            ],
            body: 'body',
            freshUntil: 1_000.0,
        );

        $store->set('key', $entry, Duration::fromMinutes(1));

        $restored = $store->get('key');

        $cache->set('http-client:broken', '{nope');
        $cache->set('http-client:scalar', '1');
        $store->delete('key');

        self::assertSame($entry->toArray(), $restored?->toArray());
        self::assertNull($store->get('key'));
        self::assertNull($store->get('broken'));
        self::assertNull($store->get('scalar'));
        self::assertSame('{nope', $cache->string('http-client:broken'));
    }
}
