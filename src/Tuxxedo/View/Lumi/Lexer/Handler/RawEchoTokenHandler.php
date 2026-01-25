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

namespace Tuxxedo\View\Lumi\Lexer\Handler;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Token\EchoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;

class RawEchoTokenHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{!';
    }

    public function getEndingSequence(): string
    {
        return '!}';
    }

    public function tokenize(
        int $startingLine,
        string $buffer,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
    ): array {
        return [
            new EchoToken(
                line: $startingLine,
                op1: TextContext::RAW->name,
            ),
            ...$expressionLexer->lex(
                startingLine: $startingLine,
                operand: \mb_trim($buffer),
            ),
            new EndToken(
                line: $startingLine,
            ),
        ];
    }
}
