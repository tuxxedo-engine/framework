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

interface CacheScopeInterface
{
    public string $name {
        get;
    }

    public ?CacheDriverConfigInterface $driver {
        get;
    }

    /**
     * @var list<class-string>
     */
    public array $allowedClasses {
        get;
    }
}
