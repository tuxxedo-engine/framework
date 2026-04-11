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

readonly class Join implements JoinInterface
{
    public function __construct(
        public JoinType $type,
        public string $identifier,
        public string $first,
        public string $second,
        public ?JoinOperator $operator = null,
    ) {
    }
}
