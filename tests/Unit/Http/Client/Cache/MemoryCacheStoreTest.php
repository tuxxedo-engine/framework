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
use Support\Temporal\ManualClock;
use Tuxxedo\Http\Client\Cache\CacheEntry;
use Tuxxedo\Http\Client\Cache\MemoryCacheStore;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Temporal\Duration;
use Tuxxedo\Temporal\Instant;

class MemoryCacheStoreTest extends TestCase
{
    public function testEntriesExpireAndCanBeDeleted(): void
    {
        $clock = new ManualClock(Instant::fromUnixTimestamp(1_000));
        $store = new MemoryCacheStore($clock);
        $entry = new CacheEntry(200, HttpVersion::V1_1, [], 'body', 1_000.0);

        $store->set('short', $entry, Duration::fromSeconds(5));
        $store->set('forever', $entry, null);
        $store->set('deleted', $entry, null);
        $store->delete('deleted');

        $before = $store->get('short');

        $clock->advance(5_000);

        self::assertSame($entry, $before);
        self::assertNull($store->get('short'));
        self::assertSame($entry, $store->get('forever'));
        self::assertNull($store->get('deleted'));
        self::assertNull(new MemoryCacheStore()->get('missing'));
    }
}
