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

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\CacheInterface;
use Tuxxedo\Temporal\DurationInterface;

readonly class CacheStore implements CacheStoreInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'http-client:',
    ) {
    }

    /**
     * @throws CacheException
     */
    public function get(
        string $key,
    ): ?CacheEntryInterface {
        $json = $this->cache->string($this->prefix . $key);

        if ($json === null) {
            return null;
        }

        $data = \json_decode($json, true);

        return \is_array($data)
            ? CacheEntry::tryFromArray($data)
            : null;
    }

    /**
     * @throws CacheException
     */
    public function set(
        string $key,
        CacheEntryInterface $entry,
        ?DurationInterface $ttl,
    ): void {
        $this->cache->set(
            key: $this->prefix . $key,
            value: \json_encode($entry->toArray(), \JSON_THROW_ON_ERROR),
            ttl: $ttl,
        );
    }

    /**
     * @throws CacheException
     */
    public function delete(
        string $key,
    ): void {
        $this->cache->delete($this->prefix . $key);
    }
}
