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

namespace Unit\View\Lumi\Lexer\Handler\Block;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\IncludeBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class IncludeBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private IncludeBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new IncludeBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testIncludeHandlerDirectiveIsInclude(): void
    {
        self::assertSame(
            'include',
            $this->handler->directive,
        );
    }

    public function testIncludeHandlerLexWithBracelessExpressionEmitsBracelessMarker(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: "'file.lumi'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(2, \count($tokens));

        $this->assertIncludeToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'braceless',
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testIncludeHandlerLexWithParenthesizedExpressionOmitsBracelessMarker(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: "('file.lumi')",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(2, \count($tokens));

        $this->assertIncludeToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testIncludeHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 7,
            expression: "'file.lumi'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertIncludeToken(
            token: $tokens[0],
            expectedLine: 7,
            expectedOp1: 'braceless',
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 7,
        );
    }
}
