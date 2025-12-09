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
use Tuxxedo\View\Lumi\Syntax\Token\LumiToken;

class EndLumiBlockHandler implements BlockHandlerInterface, AlwaysStandaloneInterface
{
    public private(set) string $directive = 'endlumi';

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

        $theme = $state->internalBuffer;
        $sourceCode = $state->textAsRawBuffer;

        $state->removeFlag(LexerStateFlag::TEXT_AS_RAW);
        $state->setInternalBuffer('');

        if (\preg_match('/^[A-Za-z]+/u', $theme) !== 1) {
            throw LexerException::fromInvalidLumiTheme(
                line: $startingLine,
                theme: $theme,
            );
        }

        return [
            new LumiToken(
                line: $startingLine,
                op1: $theme,
                op2: $sourceCode,
            ),
        ];
    }
}
