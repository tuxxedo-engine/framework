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

interface RelationPivotInterface
{
    /**
     * @var class-string
     */
    public string $relatedClass {
        get;
    }

    /**
     * @var class-string|null
     */
    public ?string $pivotClass {
        get;
    }

    public string $table {
        get;
    }

    /**
     * @var non-empty-list<string>
     */
    public array $pivotSourceColumns {
        get;
    }

    /**
     * @var non-empty-list<string>
     */
    public array $pivotTargetColumns {
        get;
    }

    /**
     * @var non-empty-list<string|int|float|bool>|null
     */
    public ?array $parentValues {
        get;
    }

    public ?string $typeColumn {
        get;
    }

    public ?string $typeValue {
        get;
    }

    /**
     * @param object|array<string, int|string>|int|string $item
     * @return non-empty-array<string, string|int|float|bool>|null
     *
     * @throws ModelException
     */
    public function keyOf(
        object|array|int|string $item,
    ): ?array;

    /**
     * @param non-empty-array<string, string|int|float|bool> $key
     */
    public function hash(
        array $key,
    ): string;

    /**
     * @param non-empty-array<string, non-empty-array<string, string|int|float|bool>> $keys
     * @return array<string, object>
     *
     * @throws ModelException
     */
    public function resolve(
        array $keys,
    ): array;

    /**
     * @return array<string, object>
     *
     * @throws ModelException
     */
    public function pivots(): array;

    /**
     * @return array<string, non-empty-array<string, string|int|float|bool>>
     *
     * @throws ModelException
     */
    public function existing(): array;

    public function isLoaded(): bool;

    public function reset(): void;

    /**
     * @param array<string, mixed> $values
     *
     * @throws ModelException
     */
    public function validatePivotValues(
        array $values,
    ): void;

    /**
     * @param non-empty-array<string, string|int|float|bool> $key
     * @param array<string, mixed> $values
     *
     * @throws ModelException
     */
    public function newPivot(
        array $key,
        array $values,
    ): object;

    /**
     * @param array<string, mixed> $values
     */
    public function fill(
        object $pivot,
        array $values,
    ): void;
}
