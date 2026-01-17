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

namespace Tuxxedo\Container;

// @todo Move this to an attribute like default implementation?
interface DefaultInitializableInterface
{
    public static function createInstance(
        ContainerInterface $container,
    ): self;
}
