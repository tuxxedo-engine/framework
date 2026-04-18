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

interface TypeHelperInterface
{
    /**
     * @return class-string|null
     */
    public function getDefaultType(
        \ReflectionParameter $reflector,
    ): ?string;

    public function getBuiltinType(
        \ReflectionParameter $reflector,
    ): ?string;

    public function isNullable(
        \ReflectionParameter $reflector,
    ): bool;
}
