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

namespace Tuxxedo\Database\Query\Statement;

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;

class ExistsStatement extends AbstractWhereStatement implements ExistsStatementInterface
{
    protected function generateSql(
        DialectInterface $dialect,
    ): string {
        return \sprintf(
            'SELECT EXISTS(SELECT 1 FROM %s%s)',
            $dialect->identifier($this->table),
            $this->generateWhereSql($dialect),
        );
    }

    public function exists(
        ?ConnectionInterface $connection = null,
    ): bool {
        return (bool) $this->execute($connection)->fetchRow()[0];
    }
}
