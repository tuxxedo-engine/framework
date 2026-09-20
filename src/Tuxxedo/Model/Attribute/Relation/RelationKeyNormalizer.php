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

namespace Tuxxedo\Model\Attribute\Relation;

use Tuxxedo\Model\ModelException;

class RelationKeyNormalizer
{
    /**
     * @param string|non-empty-array<string> $value
     * @return non-empty-list<string>
     */
    public static function toColumns(
        string|array $value,
    ): array {
        if (\is_string($value)) {
            return [
                $value,
            ];
        }

        return \array_values($value);
    }

    /**
     * @param string|non-empty-array<string> $value
     * @param non-empty-list<string> $parentPkColumns
     * @param class-string $modelClass
     * @return non-empty-list<string>
     *
     * @throws ModelException
     */
    public static function toParentOrderedColumns(
        string|array $value,
        array $parentPkColumns,
        string $modelClass,
        string $property,
        string $keyKind,
    ): array {
        $expectedArity = \sizeof($parentPkColumns);

        if (\is_string($value)) {
            if ($expectedArity !== 1) {
                throw ModelException::fromRelationForeignKeyArityMismatch(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: $keyKind,
                    expected: $expectedArity,
                    actual: 1,
                );
            }

            return [
                $value,
            ];
        }

        $actualArity = \sizeof($value);

        if ($actualArity !== $expectedArity) {
            throw ModelException::fromRelationForeignKeyArityMismatch(
                modelClass: $modelClass,
                property: $property,
                keyKind: $keyKind,
                expected: $expectedArity,
                actual: $actualArity,
            );
        }

        if (\array_is_list($value)) {
            return $value;
        }

        $ordered = [];

        foreach ($parentPkColumns as $parentColumn) {
            if (!\array_key_exists($parentColumn, $value)) {
                throw ModelException::fromRelationForeignKeyMapMissingParentColumn(
                    modelClass: $modelClass,
                    property: $property,
                    keyKind: $keyKind,
                    parentColumn: $parentColumn,
                );
            }

            $ordered[] = $value[$parentColumn];
        }

        return $ordered;
    }

    /**
     * @param string|non-empty-array<string>|null $value
     * @param non-empty-list<string> $parentPkColumns
     * @param class-string $modelClass
     * @return non-empty-list<string>|null
     *
     * @throws ModelException
     */
    public static function toParentOrderedColumnsOrNull(
        string|array|null $value,
        array $parentPkColumns,
        string $modelClass,
        string $property,
        string $keyKind,
    ): ?array {
        if ($value === null) {
            return null;
        }

        return self::toParentOrderedColumns(
            value: $value,
            parentPkColumns: $parentPkColumns,
            modelClass: $modelClass,
            property: $property,
            keyKind: $keyKind,
        );
    }
}
