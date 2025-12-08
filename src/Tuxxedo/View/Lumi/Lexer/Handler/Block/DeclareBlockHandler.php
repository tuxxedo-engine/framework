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
use Tuxxedo\View\Lumi\Syntax\Token\DeclareToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;

class DeclareBlockHandler implements BlockHandlerInterface, AlwaysExpressiveInterface
{
    public private(set) string $directive = 'declare';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        $parts = \explode('=', $expression, 2);

        if (\sizeof($parts) !== 2) {
            throw LexerException::fromInvalidDeclare(
                line: $startingLine,
            );
        }

        $op1 = \mb_trim($parts[0]);
        $op2Raw = \mb_trim($parts[1]);

        $tokens = $expressionLexer->lex(
            startingLine: $startingLine,
            operand: $op2Raw,
        );

        if (\sizeof($tokens) !== 1) {
            throw LexerException::fromInvalidDeclareLiteral(
                line: $startingLine,
            );
        }

        if (!$tokens[0] instanceof LiteralToken) {
            throw LexerException::fromInvalidDeclareLiteral(
                line: $startingLine,
            );
        }

        return [
            new DeclareToken(
                line: $startingLine,
                op1: $op1,
            ),
            $tokens[0],
            new EndToken(
                line: $startingLine,
            ),
        ];
    }
}
