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

namespace Tuxxedo\View\Lumi\Lexer\Tokens;

readonly class ElseToken implements TokenNoOpInterface
{
    public TokenType $type;
    public string $operand;

    public function __construct()
    {
        $this->type = TokenType::ELSE;
        $this->operand = '';
    }
}
