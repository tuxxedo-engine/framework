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

namespace Integration\Cache\Driver\Redis;

use Integration\Cache\Driver\AbstractCacheDriverIntegrationTestCase;
use Support\Cache\RedisServerProbe;
use Support\Cache\RedisTestEnv;
use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Cache\Driver\Redis\Config\RedisCacheDriverConfig;
use Tuxxedo\Cache\Driver\Redis\RedisCacheDriver;
use Tuxxedo\Container\Container;

class RedisCacheDriverIntegrationTest extends AbstractCacheDriverIntegrationTestCase
{
    protected function skipReason(): ?string
    {
        return RedisServerProbe::unavailableReason();
    }

    protected function createDriver(
        string $namespace,
    ): CacheDriverInterface {
        return new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                namespace: $namespace,
            ),
        );
    }

    public function testCreateBuildsWorkingDriver(): void
    {
        $driver = RedisCacheDriver::create(
            container: new Container(),
            config: new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                namespace: $this->namespace,
            ),
        );

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertTrue($driver->exists('name'));
    }

    public function testPersistentConnectionWorks(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                persistent: true,
                namespace: $this->namespace,
            ),
        );

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertTrue($driver->exists('name'));
    }

    public function testTlsAgainstPlainServerThrows(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                timeout: 0.5,
                tls: true,
            ),
        );

        self::expectException(CacheException::class);

        $driver->exists('name');
    }

    public function testPasswordAgainstServerWithoutPasswordThrows(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                password: 'not-the-password',
            ),
        );

        self::expectException(CacheException::class);

        $driver->exists('name');
    }

    public function testUnknownUserThrows(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                username: 'tuxxedo-unknown-user',
                password: 'not-the-password',
            ),
        );

        self::expectException(CacheException::class);

        $driver->exists('name');
    }

    public function testUnknownDatabaseThrows(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                database: 99,
            ),
        );

        try {
            $driver->exists('name');
        } catch (CacheException $exception) {
            self::assertStringContainsString('database 99', $exception->getMessage());
        }
    }

    public function testCorruptPayloadThrows(): void
    {
        $redis = new \Redis();

        $redis->connect(
            host: RedisTestEnv::host(),
            port: RedisTestEnv::port(),
        );

        $redis->set(
            key: $this->namespace . 'corrupt',
            value: 'not-a-serialized-value',
        );

        try {
            $this->cache->string('corrupt');
        } catch (CacheException $exception) {
            self::assertStringContainsString('failed to decode', $exception->getMessage());
        }
    }

    public function testUnreachableServerThrowsOnFirstUse(): void
    {
        $driver = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: 1,
                timeout: 0.5,
            ),
        );

        try {
            $driver->exists('name');
        } catch (CacheException $exception) {
            self::assertStringContainsString('Unable to create cache driver "redis"', $exception->getMessage());
        }
    }

    public function testSelectsConfiguredDatabase(): void
    {
        $database = new RedisCacheDriver(
            new RedisCacheDriverConfig(
                host: RedisTestEnv::host(),
                port: RedisTestEnv::port(),
                database: 1,
                namespace: $this->namespace,
            ),
        );

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertFalse($database->exists('name'));
    }
}
