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

readonly class BreakToken implements TokenInterface
{
    public string $type;

    /**
     * @param positive-int $depth
     */
    public function __construct(
        public int $depth,
    ) {
        $this->type = BuiltinTokenNames::BREAK->name;
    }
}
