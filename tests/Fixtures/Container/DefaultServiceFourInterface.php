<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixtures\Container;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\DefaultLifecycle;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: DefaultServiceTwo::class, lifecycle: Lifecycle::PERSISTENT)]
#[DefaultLifecycle(Lifecycle::TRANSIENT)]
interface DefaultServiceFourInterface
{
    public int $a {
        get;
    }

    public int $b {
        get;
    }
}
