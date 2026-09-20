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

namespace Support\Database;

use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Query\Dialect\NativeColumnTypeParserInterface;
use Tuxxedo\Database\Query\Statement\Table\Column\ColumnInterface;
use Tuxxedo\Database\Query\Statement\Table\ColumnDescriptionInterface;

class StubNativeColumnTypeParser implements NativeColumnTypeParserInterface
{
    public function parse(
        ColumnDescriptionInterface $description,
    ): ColumnInterface {
        throw DatabaseException::fromUnknownNativeColumnType(
            nativeType: $description->nativeType,
            driver: 'stub',
        );
    }
}
