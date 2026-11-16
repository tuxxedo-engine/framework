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
 * @implements PendingRelationEntryInterface<TModel>
 */
readonly class PendingRelationEntry implements PendingRelationEntryInterface
{
    /**
     * @param TModel|null $item
     * @param non-empty-array<string, string|int|float|bool>|null $key
     * @param array<string, mixed> $pivot
     */
    public function __construct(
        public ?object $item,
        public ?array $key,
        public array $pivot = [],
    ) {
    }
}
