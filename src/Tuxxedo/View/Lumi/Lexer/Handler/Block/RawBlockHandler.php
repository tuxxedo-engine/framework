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

class RawBlockHandler implements BlockHandlerInterface, AlwaysStandaloneInterface
{
    private const string END_DIRECTIVE_NAME = 'endraw';

    public private(set) string $directive = 'raw';

    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        if (
            $state->hasFlag(LexerStateFlag::TEXT_AS_RAW) ||
            (
                $state->textAsRawEndDirective !== null &&
                $state->textAsRawEndDirective !== self::END_DIRECTIVE_NAME
            )
        ) {
            throw LexerException::fromEnteringInvalidState(
                line: $startingLine,
                stateFlag: LexerStateFlag::TEXT_AS_RAW,
            );
        }

        $state->flag(LexerStateFlag::TEXT_AS_RAW);
        $state->setTextAsRawEndSequence('{%');
        $state->setTextAsRawEndDirective(self::END_DIRECTIVE_NAME);

        return [];
    }
}
