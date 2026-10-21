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

namespace Tuxxedo\Cache\Config;

use Tuxxedo\Cache\Driver\CacheDriverInterface;

interface CacheDriverConfigInterface
{
    /**
     * @var class-string<CacheDriverInterface>
     */
    public string $driverClass {
        get;
    }

    public string $namespace {
        get;
    }
}
