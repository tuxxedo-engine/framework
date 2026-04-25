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

interface PropertyReflectorInterface extends TypeInterface, AttributeInterface
{
    public \ReflectionProperty $reflector {
        get;
    }

    public string $name {
        get;
    }

    public function getValue(
        ?object $object = null,
    ): mixed;

    public function setValue(
        object $object,
        mixed $value,
    ): void;
}
