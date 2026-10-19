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

namespace Tuxxedo\Cache;

use Tuxxedo\Mapper\MapperException;
use Tuxxedo\Temporal\DurationInterface;

interface CacheInterface
{
    /**
     * @throws CacheException
     */
    #[\NoDiscard]
    public function has(
        string $key,
    ): bool;

    /**
     * @throws CacheException
     */
    public function string(
        string $key,
    ): ?string;

    /**
     * @throws CacheException
     */
    public function int(
        string $key,
    ): ?int;

    /**
     * @throws CacheException
     */
    public function float(
        string $key,
    ): ?float;

    /**
     * @throws CacheException
     */
    public function bool(
        string $key,
    ): ?bool;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return (TEnum&\UnitEnum)|null
     *
     * @throws CacheException
     */
    public function enum(
        string $key,
        string $enum,
    ): ?object;

    /**
     * @return array<array-key, string>|null
     *
     * @throws CacheException
     */
    public function arrayOfString(
        string $key,
    ): ?array;

    /**
     * @return array<array-key, int>|null
     *
     * @throws CacheException
     */
    public function arrayOfInt(
        string $key,
    ): ?array;

    /**
     * @return array<array-key, float>|null
     *
     * @throws CacheException
     */
    public function arrayOfFloat(
        string $key,
    ): ?array;

    /**
     * @return array<array-key, bool>|null
     *
     * @throws CacheException
     */
    public function arrayOfBool(
        string $key,
    ): ?array;

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return array<array-key, TEnum&\UnitEnum>|null
     *
     * @throws CacheException
     */
    public function arrayOfEnum(
        string $key,
        string $enum,
    ): ?array;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @return TClassName|null
     *
     * @throws CacheException
     */
    public function object(
        string $key,
        string $class,
    ): ?object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName|null
     *
     * @throws CacheException
     * @throws MapperException
     */
    public function mapTo(
        string $key,
        string|object $className,
    ): ?object;

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]|null
     *
     * @throws CacheException
     * @throws MapperException
     */
    public function mapToArrayOf(
        string $key,
        string|object $className,
    ): ?array;

    /**
     * @param int|float|string|bool|array<mixed>|object $value
     *
     * @throws CacheException
     */
    public function set(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl = null,
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
        ?DurationInterface $ttl = null,
    ): bool;

    /**
     * @template T of int|float|string|bool|array<mixed>|object
     *
     * @param \Closure(): T $factory
     * @return T
     *
     * @throws CacheException
     */
    public function remember(
        string $key,
        \Closure $factory,
        ?DurationInterface $ttl = null,
    ): int|float|string|bool|array|object;

    /**
     * @throws CacheException
     */
    public function increment(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int;

    /**
     * @throws CacheException
     */
    public function decrement(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int;

    /**
     * @throws CacheException
     */
    public function delete(
        string $key,
    ): void;

    /**
     * @throws CacheException
     */
    public function clear(): void;
}
