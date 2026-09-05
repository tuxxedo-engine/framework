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

namespace Tuxxedo\Model;

class MorphTypeResolver
{
    /**
     * @param class-string $class
     * @param array<string, class-string>|null $typeMap
     */
    public static function encode(
        string $class,
        ?array $typeMap,
    ): string {
        if ($typeMap === null) {
            return $class;
        }

        $alias = \array_search($class, $typeMap, true);

        return $alias === false
            ? $class
            : $alias;
    }

    /**
     * @param array<string, class-string>|null $typeMap
     * @return class-string|null
     */
    public static function resolve(
        string $typeValue,
        ?array $typeMap,
    ): ?string {
        if ($typeMap !== null) {
            return $typeMap[$typeValue] ?? null;
        }

        if (!\class_exists($typeValue)) {
            return null;
        }

        /** @var class-string $typeValue */
        return $typeValue;
    }
}
