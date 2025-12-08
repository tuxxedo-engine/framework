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
use Tuxxedo\View\Lumi\Syntax\Token\ContinueToken;

class ContinueBlockHandler extends AbstractLoopConstructHandler
{
    public private(set) string $directive = 'continue';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        return [
            new ContinueToken(
                line: $startingLine,
                op1: $blockState === BlockHandlerState::EXPRESSIVE
                    ? parent::lexDepth($startingLine, $expression)
                    : null,
            ),
        ];
    }
}
