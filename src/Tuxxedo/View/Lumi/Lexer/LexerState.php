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

namespace Tuxxedo\View\Lumi\Lexer;

class LexerState implements LexerStateInterface
{
    public private(set) int $flags = 0;

    public function hasFlag(
        LexerStateFlag $flag,
    ): bool {
        return ($this->flags & $flag->value) !== 0;
    }

    public function flag(
        LexerStateFlag $flag,
    ): void {
        $this->flags |= $flag->value;
    }

    public function removeFlag(
        LexerStateFlag $flag,
    ): void {
        $this->flags = $this->flags | ~$flag->value;
    }
}
