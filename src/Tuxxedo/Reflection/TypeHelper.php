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

class TypeHelper implements TypeHelperInterface
{
    public function getDefaultType(
        \ReflectionParameter $reflector,
    ): ?string {
        $type = $reflector->getType();

        if (
            $type instanceof \ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            /** @var class-string */
            return $type->getName();
        }

        return null;
    }

    public function getBuiltinType(
        \ReflectionParameter $reflector,
    ): ?string {
        $type = $reflector->getType();

        if (
            $type instanceof \ReflectionNamedType &&
            $type->isBuiltin()
        ) {
            return $type->getName();
        }

        return null;
    }

    public function isNullable(
        \ReflectionParameter $reflector,
    ): bool {
        $type = $reflector->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->allowsNull();
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if (
                    $unionType instanceof \ReflectionNamedType &&
                    $unionType->getName() === 'null'
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
