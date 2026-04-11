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

namespace Tuxxedo\Database\Query\Builder\Table;

use Tuxxedo\Database\Query\Builder\AbstractBuilder;

class DropTableBuilder extends AbstractBuilder implements DropTableBuilderInterface
{
    private bool $ifExists = false;

    protected function generateSql(): string
    {
        return \sprintf(
            'DROP TABLE %s%s',
            $this->ifExists
                ? 'IF EXISTS '
                : '',
            $this->connection->dialect->identifier($this->table),
        );
    }

    public function ifExists(): static
    {
        $this->ifExists = true;

        return $this;
    }
}
