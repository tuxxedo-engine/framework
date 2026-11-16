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

namespace Fixture\Container;

use Tuxxedo\Container\DefaultImplementation;

#[DefaultImplementation(class: DefaultServiceFive::class)]
interface DefaultServiceFiveInterface
{
    public CtorNoArgsService $dependency {
        get;
    }

    public string $name {
        get;
    }
}
