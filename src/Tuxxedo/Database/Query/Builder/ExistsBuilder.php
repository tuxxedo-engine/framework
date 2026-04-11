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

namespace Tuxxedo\Database\Query\Builder;

class ExistsBuilder extends AbstractWhereBuilder implements ExistsBuilderInterface
{
    protected function generateSql(): string
    {
        return \sprintf(
            'SELECT EXISTS(SELECT 1 FROM %s%s)',
            $this->connection->dialect->identifier($this->table),
            $this->generateWhereSql(),
        );
    }

    public function exists(): bool
    {
        return (bool) $this->execute()->fetchRow()[0];
    }
}
