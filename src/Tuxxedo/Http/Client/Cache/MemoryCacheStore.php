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

namespace Tuxxedo\Http\Client\Cache;

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\SystemClock;

class MemoryCacheStore implements CacheStoreInterface
{
    private readonly ClockInterface $clock;

    /**
     * @var array<string, array{0: CacheEntryInterface, 1: ?int}>
     */
    private array $entries = [];

    public function __construct(
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    public function get(
        string $key,
    ): ?CacheEntryInterface {
        if (!isset($this->entries[$key])) {
            return null;
        }

        [$entry, $expires] = $this->entries[$key];

        if ($expires !== null && $expires <= $this->clock->now()->toUnixTimestamp()) {
            unset($this->entries[$key]);

            return null;
        }

        return $entry;
    }

    public function set(
        string $key,
        CacheEntryInterface $entry,
        ?DurationInterface $ttl,
    ): void {
        $this->entries[$key] = [
            $entry,
            $ttl !== null
                ? $this->clock->now()->plus($ttl)->toUnixTimestamp()
                : null,
        ];
    }

    public function delete(
        string $key,
    ): void {
        unset($this->entries[$key]);
    }
}
