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

namespace Tuxxedo\Cache\Driver\Apcu\Config;

use Tuxxedo\Cache\Driver\Apcu\ApcuCacheDriver;

class ApcuCacheDriverConfig implements ApcuCacheDriverConfigInterface
{
    public private(set) string $driverClass = ApcuCacheDriver::class;

    public function __construct(
        public readonly ?string $namespace = null,
    ) {
    }
}
