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

namespace Tuxxedo\View\Lumi\Lexer\Token;

readonly class WhileToken implements TokenInterface
{
    public TokenType $type;

    public function __construct(
        public string $operand,
    ) {
        $this->type = TokenType::WHILE;
    }
}
