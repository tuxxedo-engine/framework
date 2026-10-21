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

namespace Tuxxedo\Cache\Driver\Redis;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Config\CacheDriverConfigInterface;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Cache\Driver\Redis\Config\RedisCacheDriverConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Temporal\DurationInterface;

class RedisCacheDriver implements CacheDriverInterface
{
    private const string NAME = 'redis';
    private const int SCAN_BATCH = 1000;

    public readonly string $namespace;
    private ?\Redis $redis = null;

    /**
     * @throws CacheException
     */
    public function __construct(
        public readonly RedisCacheDriverConfigInterface $config,
    ) {
        if (!\extension_loaded('redis')) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromDriverUnavailable(
                driver: self::NAME,
                reason: 'ext-redis is not loaded',
            );
            // @codeCoverageIgnoreEnd
        }

        $this->namespace = $config->namespace;
    }

    public static function create(
        ContainerInterface $container,
        CacheDriverConfigInterface $config,
    ): self {
        /** @var RedisCacheDriverConfigInterface $config */

        return new self($config);
    }

    /**
     * @throws CacheException
     */
    private function redis(): \Redis
    {
        return $this->redis ??= $this->connect();
    }

    /**
     * @throws CacheException
     */
    private function connect(): \Redis
    {
        $config = $this->config;
        $redis = new \Redis();
        $host = $config->tls ? 'tls://' . $config->host : $config->host;
        $context = $config->tls
            ? [
                'stream' => [
                    'verify_peer' => $config->tlsVerifyPeer,
                    'verify_peer_name' => $config->tlsVerifyPeer,
                ],
            ]
            : null;

        try {
            $connected = $config->persistent
                ? @$redis->pconnect($host, $config->port, $config->timeout, null, 0, 0.0, $context)
                : @$redis->connect($host, $config->port, $config->timeout, null, 0, 0.0, $context);

            if ($connected !== true) {
                // @codeCoverageIgnoreStart
                throw CacheException::fromDriverUnavailable(
                    driver: self::NAME,
                    reason: 'unable to connect to ' . $config->host . ':' . $config->port,
                );
                // @codeCoverageIgnoreEnd
            }

            if ($config->password !== null) {
                $credentials = $config->username !== null
                    ? [
                        $config->username,
                        $config->password,
                    ]
                    : $config->password;

                if ($redis->auth($credentials) !== true) {
                    // @codeCoverageIgnoreStart
                    throw CacheException::fromDriverUnavailable(
                        driver: self::NAME,
                        reason: 'authentication failed',
                    );
                    // @codeCoverageIgnoreEnd
                }
            }

            if ($config->database !== 0 && $redis->select($config->database) !== true) {
                throw CacheException::fromDriverUnavailable(
                    driver: self::NAME,
                    reason: 'unable to select database ' . $config->database,
                );
            }

            $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
        } catch (\RedisException $exception) {
            throw CacheException::fromDriverUnavailable(
                driver: self::NAME,
                reason: $exception->getMessage(),
            );
        }

        return $redis;
    }

    private function ttlMilliseconds(
        DurationInterface $ttl,
    ): int {
        return $ttl->seconds * 1000 + \intdiv($ttl->nanoseconds + 999_999, 1_000_000);
    }

    /**
     * @param int|float|string|bool|array<mixed>|object $value
     */
    private function encode(
        int|float|string|bool|array|object $value,
    ): string {
        if (\is_int($value)) {
            return (string) $value;
        }

        return \serialize($value);
    }

    /**
     * @param list<class-string> $allowedClasses
     * @return int|float|string|bool|array<mixed>|object
     *
     * @throws CacheException
     */
    private function decode(
        string $key,
        string $payload,
        array $allowedClasses,
    ): int|float|string|bool|array|object {
        if (\preg_match('/\A-?[0-9]+\z/', $payload) === 1 && (string) (int) $payload === $payload) {
            return (int) $payload;
        }

        $value = @\unserialize(
            $payload,
            [
                'allowed_classes' => $allowedClasses !== [] ? $allowedClasses : false,
            ],
        );

        if (
            ($value === false && $payload !== 'b:0;') ||
            (
                !\is_int($value) &&
                !\is_float($value) &&
                !\is_string($value) &&
                !\is_bool($value) &&
                !\is_array($value) &&
                !\is_object($value)
            )
        ) {
            throw CacheException::fromDriverFailure(
                driver: self::NAME,
                operation: 'decode',
                key: $key,
            );
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @return array<string|int, mixed>
     */
    private function withTtl(
        array $options,
        ?DurationInterface $ttl,
    ): array {
        if ($ttl !== null) {
            $options['px'] = $this->ttlMilliseconds($ttl);
        }

        return $options;
    }

    /**
     * @throws CacheException
     */
    private function incrementBy(
        string $key,
        int $delta,
        ?DurationInterface $ttl,
        string $operation,
    ): int {
        $redis = $this->redis();
        $fullKey = $this->namespace . $key;

        if ($ttl === null) {
            $result = $redis->incrBy($fullKey, $delta);
        } else {
            $redis->multi();
            $redis->set(
                $fullKey,
                '0',
                $this->withTtl(
                    [
                        'nx',
                    ],
                    $ttl,
                ),
            );

            $redis->incrBy($fullKey, $delta);

            $results = $redis->exec();
            $result = \is_array($results) ? ($results[1] ?? false) : false;
        }

        if (\is_int($result)) {
            return $result;
        }

        $current = $redis->get($fullKey);

        if (\is_string($current)) {
            if (\preg_match('/\A-?[0-9]+\z/', $current) === 1) {
                throw CacheException::fromCounterOverflow(
                    key: $key,
                    operation: $operation,
                );
            }

            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'int',
                actual: 'a non-integer value',
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

    /**
     * @template T
     *
     * @param \Closure(): T $callback
     * @return T
     *
     * @throws CacheException
     */
    private function guard(
        string $operation,
        string $key,
        \Closure $callback,
    ): mixed {
        try {
            return $callback();
        } catch (\RedisException $exception) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromDriverError(
                driver: self::NAME,
                operation: $operation,
                key: $key,
                previous: $exception,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function fetch(
        string $key,
        array $allowedClasses,
    ): int|float|string|bool|array|object|null {
        return $this->guard(
            operation: 'fetch',
            key: $key,
            callback: function () use ($key, $allowedClasses): int|float|string|bool|array|object|null {
                $payload = $this->redis()->get($this->namespace . $key);

                if (!\is_string($payload)) {
                    return null;
                }

                return $this->decode(
                    key: $key,
                    payload: $payload,
                    allowedClasses: $allowedClasses,
                );
            },
        );
    }

    public function exists(
        string $key,
    ): bool {
        return $this->guard(
            operation: 'check',
            key: $key,
            callback: function () use ($key): bool {
                $count = $this->redis()->exists($this->namespace . $key);

                return \is_int($count) && $count > 0;
            },
        );
    }

    public function store(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl,
    ): void {
        $stored = $this->guard(
            operation: 'store',
            key: $key,
            callback: fn (): mixed => $this->redis()->set(
                $this->namespace . $key,
                $this->encode($value),
                $this->withTtl([], $ttl),
            ),
        );

        if ($stored !== true) {
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
        return $this->guard(
            operation: 'add',
            key: $key,
            callback: fn (): bool => $this->redis()->set(
                $this->namespace . $key,
                $this->encode($value),
                $this->withTtl(
                    [
                        'nx',
                    ],
                    $ttl,
                ),
            ) === true,
        );
    }

    public function increment(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        return $this->guard(
            operation: 'increment',
            key: $key,
            callback: fn (): int => $this->incrementBy(
                key: $key,
                delta: $by,
                ttl: $ttl,
                operation: 'increment',
            ),
        );
    }

    public function decrement(
        string $key,
        int $by,
        ?DurationInterface $ttl,
    ): int {
        return $this->guard(
            operation: 'decrement',
            key: $key,
            callback: fn (): int => $this->incrementBy(
                key: $key,
                delta: -$by,
                ttl: $ttl,
                operation: 'decrement',
            ),
        );
    }

    public function remove(
        string $key,
    ): void {
        $this->guard(
            operation: 'remove',
            key: $key,
            callback: fn (): mixed => $this->redis()->unlink($this->namespace . $key),
        );
    }

    public function clear(
        string $prefix,
    ): void {
        $this->guard(
            operation: 'clear',
            key: $prefix . '*',
            callback: function () use ($prefix): void {
                $redis = $this->redis();
                $pattern = \addcslashes($this->namespace . $prefix, '*?[]\\') . '*';
                $iterator = null;

                do {
                    $keys = $redis->scan($iterator, $pattern, self::SCAN_BATCH);

                    if (\is_array($keys) && $keys !== []) {
                        $redis->unlink($keys);
                    }
                } while ($iterator > 0);
            },
        );
    }
}
