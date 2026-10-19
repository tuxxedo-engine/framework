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

namespace Tuxxedo\Cache\Driver\Apcu;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Config\CacheDriverConfigInterface;
use Tuxxedo\Cache\Driver\Apcu\Config\ApcuCacheDriverConfigInterface;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\DurationInterface;

class ApcuCacheDriver implements CacheDriverInterface
{
    private const string NAME = 'apcu';

    public readonly string $namespace;

    /**
     * @throws CacheException
     */
    public function __construct(
        ApcuCacheDriverConfigInterface $config,
    ) {
        if (!\extension_loaded('apcu')) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromDriverUnavailable(
                driver: self::NAME,
                reason: 'ext-apcu is not loaded',
            );
            // @codeCoverageIgnoreEnd
        }

        if (!\apcu_enabled()) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromDriverUnavailable(
                driver: self::NAME,
                reason: 'APCu is disabled (apc.enabled, or apc.enable_cli on the CLI)',
            );
            // @codeCoverageIgnoreEnd
        }

        $this->namespace = $config->namespace ?? '';
    }

    public static function create(
        ContainerInterface $container,
        CacheDriverConfigInterface $config,
    ): self {
        /** @var ApcuCacheDriverConfigInterface $config */

        return new self($config);
    }

    private function ttlSeconds(
        ?DurationInterface $ttl,
    ): int {
        if ($ttl === null) {
            return 0;
        }

        return $ttl->seconds + ($ttl->nanoseconds > 0 ? 1 : 0);
    }

    /**
     * @throws CacheException
     */
    private function counterResult(
        string $key,
        mixed $result,
        bool $success,
        string $operation,
    ): int {
        if ($success && \is_int($result)) {
            return $result;
        }

        if (\apcu_exists($this->namespace . $key) === true) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'int',
                actual: \get_debug_type(\apcu_fetch($this->namespace . $key)),
            );
        }

        // @codeCoverageIgnoreStart
        throw CacheException::fromDriverFailure(
            driver: self::NAME,
            operation: $operation,
            key: $key,
        );
        // @codeCoverageIgnoreEnd
    }

    public function fetch(
        string $key,
        array $allowedClasses,
    ): int|float|string|bool|array|object|null {
        $success = false;
        $value = \apcu_fetch($this->namespace . $key, $success);

        if (
            !$success ||
            (
                !\is_int($value) &&
                !\is_float($value) &&
                !\is_string($value) &&
                !\is_bool($value) &&
                !\is_array($value) &&
                !\is_object($value)
            )
        ) {
            return null;
        }

        return $value;
    }

    public function exists(
        string $key,
    ): bool {
        return \apcu_exists($this->namespace . $key) === true;
    }

    public function store(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): void {
        if (\apcu_store($this->namespace . $key, $value, $this->ttlSeconds($ttl)) !== true) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromDriverFailure(
                driver: self::NAME,
                operation: 'store',
                key: $key,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    #[\NoDiscard]
    public function add(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): bool {
        return \apcu_add($this->namespace . $key, $value, $this->ttlSeconds($ttl)) === true;
    }

    public function increment(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        $success = false;
        $result = \apcu_inc($this->namespace . $key, $by, $success, $this->ttlSeconds($ttl));

        /** @var bool $success */

        if ($success && \is_int($result) && $result < \PHP_INT_MIN + $by) {
            \apcu_dec($this->namespace . $key, $by);

            throw CacheException::fromCounterOverflow(
                key: $key,
                operation: 'increment',
            );
        }

        return $this->counterResult(
            key: $key,
            result: $result,
            success: $success,
            operation: 'increment',
        );
    }

    public function decrement(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        $success = false;
        $result = \apcu_dec($this->namespace . $key, $by, $success, $this->ttlSeconds($ttl));

        /** @var bool $success */

        if ($success && \is_int($result) && $result > \PHP_INT_MAX - $by) {
            \apcu_inc($this->namespace . $key, $by);

            throw CacheException::fromCounterOverflow(
                key: $key,
                operation: 'decrement',
            );
        }

        return $this->counterResult(
            key: $key,
            result: $result,
            success: $success,
            operation: 'decrement',
        );
    }

    public function remove(
        string $key,
    ): void {
        \apcu_delete($this->namespace . $key);
    }

    public function clear(
        string $prefix,
    ): void {
        $fullPrefix = $this->namespace . $prefix;

        if ($fullPrefix === '') {
            \apcu_clear_cache();

            return;
        }

        \apcu_delete(
            new \APCUIterator(
                '/\A' . \preg_quote($fullPrefix, '/') . '/',
                \APC_ITER_KEY,
            ),
        );
    }
}
