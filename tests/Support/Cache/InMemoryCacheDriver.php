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

namespace Support\Cache;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Config\CacheDriverConfigInterface;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\DurationInterface;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\SystemClock;

class InMemoryCacheDriver implements CacheDriverInterface
{
    /**
     * @var array<array-key, array{value: int|float|string|bool|array<mixed>|object, expiresAt: InstantInterface|null}>
     */
    public array $entries = [];

    /**
     * @var list<string>
     */
    public array $clearedPrefixes = [];

    public ClockInterface $clock;

    public function __construct(
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    public static function create(
        ContainerInterface $container,
        CacheDriverConfigInterface $config,
    ): self {
        return new self();
    }

    private function live(
        string $key,
    ): bool {
        if (!\array_key_exists($key, $this->entries)) {
            return false;
        }

        $expiresAt = $this->entries[$key]['expiresAt'];

        if ($expiresAt !== null && !$this->clock->now()->isBefore($expiresAt)) {
            unset($this->entries[$key]);

            return false;
        }

        return true;
    }

    private function expiresAt(
        ?DurationInterface $ttl,
    ): ?InstantInterface {
        return $ttl !== null ? $this->clock->now()->plus($ttl) : null;
    }

    private function incrementBy(
        string $key,
        int $delta,
        ?DurationInterface $ttl,
    ): int {
        if (!$this->live($key)) {
            $this->store(
                key: $key,
                value: 0,
                ttl: $ttl,
            );
        }

        $value = $this->entries[$key]['value'];

        if (!\is_int($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'int',
                actual: \get_debug_type($value),
            );
        }

        $this->entries[$key]['value'] = $value + $delta;

        return $value + $delta;
    }

    public function fetch(
        string $key,
    ): int|float|string|bool|array|object|null {
        return $this->live($key) ? $this->entries[$key]['value'] : null;
    }

    public function exists(
        string $key,
    ): bool {
        return $this->live($key);
    }

    public function store(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): void {
        $this->entries[$key] = [
            'value' => $value,
            'expiresAt' => $this->expiresAt($ttl),
        ];
    }

    #[\NoDiscard]
    public function add(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): bool {
        if ($this->live($key)) {
            return false;
        }

        $this->store(
            key: $key,
            value: $value,
            ttl: $ttl,
        );

        return true;
    }

    public function increment(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        return $this->incrementBy(
            key: $key,
            delta: $by,
            ttl: $ttl,
        );
    }

    public function decrement(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        return $this->incrementBy(
            key: $key,
            delta: -$by,
            ttl: $ttl,
        );
    }

    public function remove(
        string $key,
    ): void {
        unset($this->entries[$key]);
    }

    public function clear(
        string $prefix,
    ): void {
        $this->clearedPrefixes[] = $prefix;

        foreach (\array_keys($this->entries) as $key) {
            if (\str_starts_with((string) $key, $prefix)) {
                unset($this->entries[$key]);
            }
        }
    }
}
