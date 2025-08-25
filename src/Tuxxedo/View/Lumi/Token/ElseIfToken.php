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

namespace Tuxxedo\View\Lumi\Token;

readonly class ElseIfToken implements TokenInterface
{
    public string $type;
    public null $op1;
    public null $op2;

    public function __construct(
        public int $line,
    ) {
        $this->type = BuiltinTokenNames::ELSEIF->name;
        $this->op1 = null;
        $this->op2 = null;
    }
}
