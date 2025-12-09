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

    public string $internalBuffer {
        get;
    }

    public ?string $textAsRawEndSequence {
        get;
    }

    public ?string $textAsRawEndDirective {
        get;
    }

    public string $textAsRawBuffer {
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

    public function isClean(): bool;

    public function setInternalBuffer(
        string $buffer,
    ): void;

    public function setTextAsRawBuffer(
        string $buffer,
    ): void;

    public function appendTextAsRawBuffer(
        string $buffer,
    ): void;

    public function setTextAsRawEndSequence(
        ?string $sequence,
    ): void;

    public function setTextAsRawEndDirective(
        ?string $directive,
    ): void;
}
