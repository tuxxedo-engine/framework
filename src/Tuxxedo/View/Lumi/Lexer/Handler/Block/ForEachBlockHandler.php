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
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForEachToken;

class ForEachBlockHandler implements BlockHandlerInterface, AlwaysExpressiveInterface
{
    public private(set) string $directive = 'foreach';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        if (\preg_match('/^(.+?)\s+as\s+(\w+)(?:\s*=>\s*(\w+))?$/ui', $expression, $matches) !== 1) {
            throw LexerException::fromInvalidForeachSyntax(
                line: $startingLine,
            );
        }

        $expr = $matches[1];
        $key = $matches[2];
        $value = isset($matches[3]) ? $matches[3] : null;

        if ($value === null) {
            $value = $key;
            $key = null;
        }

        return [
            new ForEachToken(
                line: $startingLine,
                op1: $value,
                op2: $key,
            ),
            ...$expressionLexer->lex(
                startingLine: $startingLine,
                operand: $expr,
            ),
            new EndToken(
                line: $startingLine,
            ),
        ];
    }
}
