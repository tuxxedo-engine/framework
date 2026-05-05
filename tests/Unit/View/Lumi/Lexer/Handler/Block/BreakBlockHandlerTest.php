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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BreakBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class BreakBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private BreakBlockHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new BreakBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testBreakHandlerDirectiveIsBreak(): void
    {
        self::assertSame(
            'break',
            $this->handler->directive,
        );
    }

    public function testStandaloneEmitsBreakTokenWithNullDepth(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testStandaloneIgnoresExpression(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '2',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testExpressiveWithEmptyExpressionEmitsBreakTokenWithNullDepth(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testExpressiveWithDepthOneCanonicalizesToNull(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '1',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testExpressiveWithDepthGreaterThanOneEmitsTokenWithDepth(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '5',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: '5',
        );
    }

    public function testExpressiveTrimsWhitespaceAroundDepth(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 1,
            expression: '   3   ',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: '3',
        );
    }

    public function testExpressiveWithNonNumericDepthThrows(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: 'abc',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testExpressiveWithLeadingZeroDepthThrows(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: '02',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testExpressiveWithNegativeDepthThrows(): void
    {
        $this->expectException(LexerException::class);

        $this->handler->lex(
            startingLine: 1,
            expression: '-1',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testBreakHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->handler->lex(
            startingLine: 42,
            expression: '2',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBreakToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: '2',
        );
    }
}
