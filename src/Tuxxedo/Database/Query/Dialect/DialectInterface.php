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

use Tuxxedo\Database\SqlException;

interface DialectInterface
{
    /**
     * @var string[]
     */
    public array $quotations {
        get;
    }

    public function placeholder(
        int $position,
    ): string;

    /**
     * @throws SqlException
     */
    public function identifier(
        string $name,
    ): string;
}
