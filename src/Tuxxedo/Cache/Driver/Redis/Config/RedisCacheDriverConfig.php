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

namespace Tuxxedo\Cache\Driver\Redis\Config;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Cache\Driver\Redis\RedisCacheDriver;

class RedisCacheDriverConfig implements RedisCacheDriverConfigInterface
{
    public private(set) string $driverClass = RedisCacheDriver::class;

    /**
     * @throws CacheException
     */
    public function __construct(
        public readonly string $namespace,
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 6379,
        public readonly int $database = 0,
        public readonly ?string $username = null,
        #[\SensitiveParameter] public readonly ?string $password = null,
        public readonly float $timeout = 2.0,
        public readonly bool $persistent = false,
        public readonly bool $tls = false,
        public readonly bool $tlsVerifyPeer = true,
    ) {
        if ($namespace === '') {
            throw CacheException::fromInvalidNamespace();
        }
    }
}
