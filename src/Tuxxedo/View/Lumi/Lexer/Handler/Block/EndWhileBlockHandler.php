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
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;

class EndWhileBlockHandler implements BlockHandlerInterface, AlwaysStandaloneInterface
{
    public private(set) string $directive = 'endwhile';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        return [
            new EndWhileToken(
                line: $startingLine,
            ),
        ];
    }
}
