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

use Tuxxedo\Database\Query\Dialect\DialectInterface;

interface SubqueryRenderableInterface
{
    /**
     * @return array{sql: string, parameters: array<string, string|int|float|bool|null|array<string|int|float|bool|null>>}
     */
    public function renderSubquery(
        DialectInterface $dialect,
        string $parameterPrefix,
    ): array;
}
