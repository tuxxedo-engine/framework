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

namespace Tuxxedo\Container\Reflection;

interface ParameterInterface
{
    public \ReflectionParameter $reflector {
        get;
    }

    /**
     * @return class-string|null
     */
    public function getDefaultType(): ?string;

    public function getBuiltinType(): ?string;
    public function isNullable(): bool;
}
