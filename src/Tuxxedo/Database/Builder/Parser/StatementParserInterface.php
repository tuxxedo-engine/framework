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

namespace Tuxxedo\Database\Builder\Parser;

use Tuxxedo\Database\Builder\Dialect\DialectInterface;
use Tuxxedo\Database\SqlException;

interface StatementParserInterface
{
    public DialectInterface $dialect {
        get;
    }

    /**
     * @param array<string|int|float|bool|null|array<string|int|float|bool|null>> $parameters
     *
     * @throws SqlException
     */
    public function parse(
        string $sql,
        array $parameters = [],
    ): StatementParserResultInterface;
}
