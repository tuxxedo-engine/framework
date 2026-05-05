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
use Tuxxedo\View\Lumi\Lexer\Handler\RawEchoTokenHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Syntax\TextContext;

class RawEchoTokenHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private RawEchoTokenHandler $handler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->handler = new RawEchoTokenHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testGetStartingSequenceReturnsRawEchoOpen(): void
    {
        self::assertSame(
            '{!',
            $this->handler->getStartingSequence(),
        );
    }

    public function testGetEndingSequenceReturnsRawEchoClose(): void
    {
        self::assertSame(
            '!}',
            $this->handler->getEndingSequence(),
        );
    }

    public function testTokenizeWrapsSingleIdentifierWithRawEchoAndEndTokens(): void
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
            expectedOp1: TextContext::RAW->name,
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

    public function testTokenizeMarksLeadingEchoTokenAsRaw(): void
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
            expectedOp1: TextContext::RAW->name,
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
            expectedOp1: TextContext::RAW->name,
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
            expectedOp1: TextContext::RAW->name,
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
            expectedOp1: TextContext::RAW->name,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }
}
