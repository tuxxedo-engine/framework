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
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;

class BlockBlockHandler implements BlockHandlerInterface, AlwaysExpressiveInterface
{
    public private(set) string $directive = 'block';

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
            new BlockToken(
                line: $startingLine,
                op1: $expression[0]->op1,
            ),
        ];
    }
}
