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

namespace Tuxxedo\Reflection;

interface MethodReflectorInterface extends AttributeInterface
{
    public \ReflectionMethod $reflector {
        get;
    }

    /**
     * @throws \ReflectionException
     */
    public function parameter(
        string $name,
    ): ParameterReflectorInterface;
}
