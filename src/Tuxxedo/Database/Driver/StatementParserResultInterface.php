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

interface StatementParserResultInterface
{
    public string $sql {
        get;
    }

    /**
     * @var array<string|int|float|bool|null>
     */
    public array $bindings {
        get;
    }
}
