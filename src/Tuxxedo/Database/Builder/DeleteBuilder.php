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

class DeleteBuilder extends AbstractWhereBuilder implements DeleteBuilderInterface
{
    protected function generateSql(): string
    {
        // @todo Implement
        // @todo Call parent

        return '';
    }

    public function limit(
        int $limit,
    ): static {
        // @todo Implement

        return $this;
    }
}
