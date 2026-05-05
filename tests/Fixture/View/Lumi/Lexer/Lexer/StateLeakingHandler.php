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

namespace Fixture\View\Lumi\Lexer\Lexer;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\TokenHandlerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerStateFlag;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;

class StateLeakingHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{leak';
    }

    public function getEndingSequence(): string
    {
        return '}';
    }

    public function tokenize(
        int $startingLine,
        string $buffer,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
    ): array {
        $state->flag(LexerStateFlag::TEXT_AS_RAW);

        return [];
    }
}
