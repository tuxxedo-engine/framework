<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database\Dialect;

class MysqlDialect implements DialectInterface
{
    public private(set) array $quotations = [
        '\'',
        '"',
        '`',
    ];

    public function placeholder(int $position): string
    {
        return '?';
    }
}
