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
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;

class CommentTokenHandler implements TokenHandlerInterface
{
    public function getStartingSequence(): string
    {
        return '{#';
    }

    public function getEndingSequence(): string
    {
        return '#}';
    }

    public function tokenize(
        int $startingLine,
        string $buffer,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
    ): array {
        return [
            new CommentToken(
                line: $startingLine,
                op1: \mb_trim($buffer),
            ),
        ];
    }
}
