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

namespace App\Service\Lazy;

use App\Service\Logger\CustomLoggerInterface;
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: LazyService::class, lifecycle: Lifecycle::SINGLETON)]
interface LazyServiceInterface
{
    public CustomLoggerInterface $logger {
        get;
    }
}
