<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Parser;

class ParserState implements ParserStateInterface
{
    public private(set) int $loopDepth = 0;
    public private(set) int $nestingDepth = 0;

    public function enterLoop(): void
    {
        $this->loopDepth++;
    }

    public function leaveLoop(): void
    {
        if ($this->loopDepth === 0) {
            throw ParserException::fromUnexpectedLoopExit();
        }

        $this->loopDepth--;
    }

    public function enterNesting(): void
    {
        $this->nestingDepth++;
    }

    public function leaveNesting(): void
    {
        if ($this->nestingDepth === 0) {
            throw ParserException::fromUnexpectedNestingExit();
        }

        $this->nestingDepth--;
    }
}
