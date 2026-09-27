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

namespace Tuxxedo\View\Lumi\Lexer\Handler\Block;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\YieldToken;

class YieldBlockHandler implements BlockHandlerInterface, AlwaysExpressiveInterface
{
    public private(set) string $directive = 'yield';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        $expression = $expressionLexer->lex(
            startingLine: $startingLine,
            operand: $expression,
        );

        if (
            \sizeof($expression) !== 1 ||
            !$expression[0] instanceof IdentifierToken
        ) {
            throw LexerException::fromInvalidBlockName(
                line: $startingLine,
            );
        }

        return [
            new YieldToken(
                line: $startingLine,
                op1: $expression[0]->op1,
            ),
        ];
    }
}
