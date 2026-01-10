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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializableInterface;

class DefaultServiceTwo implements DefaultServiceTwoInterface, DefaultInitializableInterface
{
    private function __construct()
    {
    }

    public static function createInstance(
        ContainerInterface $container,
    ): DefaultInitializableInterface {
        return new DefaultServiceTwo();
    }
}
