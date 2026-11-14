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

use Tuxxedo\Temporal\DurationInterface;

interface CacheStoreInterface
{
    public function get(
        string $key,
    ): ?CacheEntryInterface;

    public function set(
        string $key,
        CacheEntryInterface $entry,
        ?DurationInterface $ttl,
    ): void;

    public function delete(
        string $key,
    ): void;
}
