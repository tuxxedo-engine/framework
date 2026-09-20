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

namespace Tuxxedo\Database\Query\Dialect;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescriptionInterface;

interface NativeColumnTypeParserInterface
{
    /**
     * @throws DatabaseException
     */
    public function parse(
        ColumnDescriptionInterface $description,
    ): ColumnInterface;
}
