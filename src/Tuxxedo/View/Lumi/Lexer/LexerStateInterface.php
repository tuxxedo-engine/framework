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

interface LexerStateInterface
{
    public int $flags {
        get;
    }

    public function hasFlag(
        LexerStateFlag $flag,
    ): bool;

    public function flag(
        LexerStateFlag $flag,
    ): void;

    public function removeFlag(
        LexerStateFlag $flag,
    ): void;
}
