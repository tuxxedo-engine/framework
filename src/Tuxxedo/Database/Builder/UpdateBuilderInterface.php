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

namespace Tuxxedo\Database\Builder;

// @todo Implement
interface UpdateBuilderInterface extends WhereBuilderInterface
{
    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static;

    public function increment(
        string $column,
        int|float $amount = 1,
    ): static;

    public function decrement(
        string $column,
        int|float $amount = 1,
    ): static;
}
