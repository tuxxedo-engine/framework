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

readonly class ElseToken implements TokenInterface
{
    public string $type;
    public null $op1;
    public null $op2;

    public function __construct()
    {
        $this->type = BuiltinTokenNames::ELSE->name;
        $this->op1 = null;
        $this->op2 = null;
    }
}
