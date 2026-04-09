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

namespace Tuxxedo\Database\Driver;

readonly class StatementParserResult implements StatementParserResultInterface
{
    /**
     * @param array<string|int|float|bool|null> $parameters
     */
    public function __construct(
        public string $sql,
        public array $parameters = [],
    ) {
    }
}
