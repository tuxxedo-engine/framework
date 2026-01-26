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

namespace Tuxxedo\View\Lumi\Lexer\Handler\Block;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IncludeToken;

class IncludeBlockHandler implements BlockHandlerInterface, AlwaysExpressiveInterface
{
    public private(set) string $directive = 'include';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        $firstCharacter = \mb_substr($expression, 0, 1);
        $lastCharacter = \mb_substr($expression, -1);

        if ($firstCharacter !== '(') {
            $expression = '(' . $expression;
        }

        if ($lastCharacter !== ')') {
            $expression .= ')';
        }

        $expression = 'include' . $expression;

        return [
            new IncludeToken(
                line: $startingLine,
                op1: $firstCharacter !== '('
                    ? 'braceless'
                    : null,
            ),
            ...$expressionLexer->lex(
                startingLine: $startingLine,
                operand: $expression,
            ),
            new EndToken(
                line: $startingLine,
            ),
        ];
    }
}
