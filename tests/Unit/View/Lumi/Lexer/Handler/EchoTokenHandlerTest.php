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

namespace Unit\View\Lumi\Lexer\Handler;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\EchoTokenHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class EchoTokenHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private EchoTokenHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new EchoTokenHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testGetStartingSequenceReturnsEchoOpen(): void
    {
        self::assertSame(
            '{{',
            $this->handler->getStartingSequence(),
        );
    }

    public function testGetEndingSequenceReturnsEchoClose(): void
    {
        self::assertSame(
            '}}',
            $this->handler->getEndingSequence(),
        );
    }

    public function testTokenizeWrapsSingleIdentifierWithEchoAndEndTokens(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(3, $tokens);

        $this->assertEchoToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'foo',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testTokenizeTrimsSurroundingWhitespaceBeforeDelegatingToExpressionLexer(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: '   foo   ',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(3, $tokens);

        $this->assertEchoToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'foo',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testTokenizeProducesLinearStreamForCompoundExpression(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: 'foo + bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertGreaterThan(3, \count($tokens));

        $this->assertEchoToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testTokenizePropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 7,
            buffer: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        self::assertCount(3, $tokens);

        $this->assertEchoToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }

    public function testTokenizeEchoTokenHasNoContextMarker(): void
    {
        $tokens = $this->handler->tokenize(
            startingLine: 1,
            buffer: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
        );

        $this->assertEchoToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }
}
