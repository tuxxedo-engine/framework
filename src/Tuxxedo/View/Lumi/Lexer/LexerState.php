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
    public private(set) string $internalBuffer = '';
    public private(set) ?string $textAsRawEndSequence = null;
    public private(set) ?string $textAsRawEndDirective = null;
    public private(set) string $textAsRawBuffer = '';

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
        $this->flags = $this->flags & ~$flag->value;

        $flag->onRemove($this);
    }

    public function isClean(): bool
    {
        return $this->flags === LexerStateFlag::NONE->value &&
            $this->internalBuffer === '' &&
            $this->textAsRawEndSequence === null &&
            $this->textAsRawEndDirective === null &&
            $this->textAsRawBuffer === '';
    }

    public function setInternalBuffer(
        string $buffer,
    ): void {
        $this->internalBuffer = $buffer;
    }

    public function setTextAsRawBuffer(
        string $buffer,
    ): void {
        $this->textAsRawBuffer = $buffer;
    }

    public function appendTextAsRawBuffer(
        string $buffer,
    ): void {
        $this->textAsRawBuffer .= $buffer;
    }

    public function setTextAsRawEndSequence(
        ?string $sequence,
    ): void {
        $this->textAsRawEndSequence = $sequence;
    }

    public function setTextAsRawEndDirective(
        ?string $directive,
    ): void {
        $this->textAsRawEndDirective = $directive;
    }
}
