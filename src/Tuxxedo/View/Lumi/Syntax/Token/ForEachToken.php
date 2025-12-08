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

namespace Tuxxedo\View\Lumi\Syntax\Token;

readonly class ForEachToken extends AbstractToken
{
    public function __construct(
        public int $line,
        public string $op1,
        public ?string $op2 = null,
    ) {
    }
}
