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
use Tuxxedo\View\Lumi\Lexer\LexerStateFlag;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class EndRawBlockHandler implements BlockHandlerInterface, AlwaysStandaloneInterface
{
    public private(set) string $directive = 'endraw';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        if (!$state->hasFlag(LexerStateFlag::TEXT_AS_RAW)) {
            throw LexerException::fromInvalidTextAsRawEnd(
                line: $startingLine,
                tokenName: $this->directive,
            );
        }

        $textBuffer = $state->textAsRawBuffer;

        $state->removeFlag(LexerStateFlag::TEXT_AS_RAW);

        return [
            new TextToken(
                line: $startingLine,
                op1: $textBuffer,
            ),
        ];
    }
}
