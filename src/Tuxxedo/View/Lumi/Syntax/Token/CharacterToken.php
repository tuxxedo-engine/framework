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

readonly class CharacterToken implements TokenInterface, ExpressionTokenInterface
{
    public string $type;
    public null $op2;

    public function __construct(
        public int $line,
        public string $op1,
    ) {
        $this->type = BuiltinTokenNames::CHARACTER->name;
        $this->op2 = null;
    }
}
