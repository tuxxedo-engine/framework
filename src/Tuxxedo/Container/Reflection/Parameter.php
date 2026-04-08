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

class Parameter implements ParameterInterface
{
    public function __construct(
        public readonly \ReflectionParameter $reflector,
    ) {
    }

    public function getDefaultType(): ?string
    {
        $type = $this->reflector->getType();

        if (
            $type instanceof \ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            /** @var class-string */
            return $type->getName();
        }

        return null;
    }
}
