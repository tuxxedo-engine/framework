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

namespace Tuxxedo\Database\Query\Statement\Table\Operation;

class AddIndex implements AlterOperationInterface
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        public readonly array $columns,
        public readonly ?string $name = null,
    ) {
    }
}
