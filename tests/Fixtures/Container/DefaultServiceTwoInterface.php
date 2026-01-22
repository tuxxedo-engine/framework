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

#[DefaultImplementation(class: DefaultServiceTwo::class)]
interface DefaultServiceTwoInterface
{
    /**
     * @var array<mixed>
     */
    public array $arguments {
        get;
    }
}
