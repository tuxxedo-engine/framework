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

/**
 * @template TModel of object
 *
 * @extends QueryableInterface<TModel>
 */
interface RelationInterface extends QueryableInterface
{
    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>
     */
    public array $pendingAdds {
        get;
    }

    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>
     */
    public array $pendingRemoves {
        get;
    }

    /**
     * @var array<string, array{item: TModel|null, key: non-empty-array<string, string|int|float|bool>|null, pivot: array<string, mixed>}>|null
     */
    public ?array $pendingSync {
        get;
    }

    public ?RelationPivotInterface $pivot {
        get;
    }

    /**
     * @param TModel|\BackedEnum|array<string, int|string>|int|string $item
     * @param array<string, mixed> $pivot
     *
     * @throws ModelException
     */
    public function add(
        object|array|int|string $item,
        array $pivot = [],
    ): void;

    /**
     * @param TModel|\BackedEnum|array<string, int|string>|int|string $item
     *
     * @throws ModelException
     */
    public function remove(
        object|array|int|string $item,
    ): void;

    /**
     * @param iterable<TModel|\BackedEnum|array<string, int|string>|int|string> $items
     *
     * @throws ModelException
     */
    public function sync(
        iterable $items,
    ): void;

    /**
     * @param TModel|\BackedEnum|array<string, int|string>|int|string $item
     *
     * @throws ModelException
     */
    public function pivotFor(
        object|array|int|string $item,
    ): ?object;

    public function hasPendingChanges(): bool;

    public function clearPending(): void;
}
