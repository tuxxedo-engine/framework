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

namespace Tuxxedo\Cache\Driver;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Config\CacheDriverConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\DurationInterface;

interface CacheDriverInterface
{
    /**
     * @throws CacheException
     */
    public static function create(
        ContainerInterface $container,
        CacheDriverConfigInterface $config,
    ): self;

    /**
     * @param list<class-string> $allowedClasses
     * @return int|float|string|bool|array<mixed>|object|null
     *
     * @throws CacheException
     */
    public function fetch(
        string $key,
        array $allowedClasses,
    ): int|float|string|bool|array|object|null;

    /**
     * @throws CacheException
     */
    public function exists(
        string $key,
    ): bool;

    /**
     * @param int|float|string|bool|array<mixed>|object $value
     *
     * @throws CacheException
     */
    public function store(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): void;

    /**
     * @param int|float|string|bool|array<mixed>|object $value
     *
     * @throws CacheException
     */
    #[\NoDiscard]
    public function add(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): bool;

    /**
     * @throws CacheException
     */
    public function increment(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int;

    /**
     * @throws CacheException
     */
    public function decrement(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int;

    /**
     * @throws CacheException
     */
    public function remove(
        string $key,
    ): void;

    /**
     * @throws CacheException
     */
    public function clear(
        string $prefix,
    ): void;
}
