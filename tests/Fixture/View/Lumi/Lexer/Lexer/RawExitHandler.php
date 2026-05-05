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
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class RawExitHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{endraw';
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
        $textBuffer = $state->textAsRawBuffer;

        $state->removeFlag(LexerStateFlag::TEXT_AS_RAW);

        return [
            new TextToken(
                line: $startingLine,
                op1: $textBuffer,
                op2: TextContext::RAW->name,
            ),
        ];
    }
}
