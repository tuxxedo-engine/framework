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

class CountBuilder extends AbstractWhereBuilder implements CountBuilderInterface
{
    public function column(
        string $column = '*',
    ): static {
        // @todo Implement

        return $this;
    }

    public function count(): int
    {
        // @todo Implement

        return 0;
    }
}
