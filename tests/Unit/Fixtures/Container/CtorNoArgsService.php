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

namespace Unit\Fixtures\Container;

class CtorNoArgsService
{
    public readonly bool $ready;

    public function __construct()
    {
        $this->ready = true;
    }
}
