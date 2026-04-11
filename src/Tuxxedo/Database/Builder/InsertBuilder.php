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

class InsertBuilder extends AbstractBuilder implements InsertBuilderInterface
{
    protected function generateSql(): string
    {
        // @todo Implement

        return '';
    }

    public function isBulk(): bool
    {
        // @todo Implement

        return false;
    }

    public function set(
        string $column,
        string|int|float|bool|null $value,
    ): static {
        // @todo Implement

        return $this;
    }

    /**
     * @param array<array<string, string|int|float|bool|null>> $rows
     */
    public function values(
        array $rows,
    ): static {
        // @todo Implement

        return $this;
    }
}
