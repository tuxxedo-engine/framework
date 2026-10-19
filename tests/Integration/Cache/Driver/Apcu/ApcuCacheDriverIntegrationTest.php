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

namespace Integration\Cache\Driver\Apcu;

use Integration\Cache\Driver\AbstractCacheDriverIntegrationTestCase;
use Tuxxedo\Cache\Driver\Apcu\ApcuCacheDriver;
use Tuxxedo\Cache\Driver\Apcu\Config\ApcuCacheDriverConfig;
use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Container\Container;

class ApcuCacheDriverIntegrationTest extends AbstractCacheDriverIntegrationTestCase
{
    protected function skipReason(): ?string
    {
        if (!\extension_loaded('apcu')) {
            return 'ext-apcu is not loaded';
        }

        if (!\apcu_enabled()) {
            return 'APCu is disabled (set apc.enable_cli=1)';
        }

        return null;
    }

    protected function createDriver(
        string $namespace,
    ): CacheDriverInterface {
        return new ApcuCacheDriver(
            new ApcuCacheDriverConfig(
                namespace: $namespace,
            ),
        );
    }

    public function testCreateBuildsWorkingDriver(): void
    {
        $driver = ApcuCacheDriver::create(
            container: new Container(),
            config: new ApcuCacheDriverConfig(
                namespace: $this->namespace,
            ),
        );

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        self::assertTrue($driver->exists('name'));
    }

    public function testClearWithoutNamespaceClearsEverything(): void
    {
        $driver = $this->createDriver('');

        $this->cache->set(
            key: 'name',
            value: 'Kalle',
        );

        $driver->clear('');

        self::assertFalse($this->cache->has('name'));
    }
}
