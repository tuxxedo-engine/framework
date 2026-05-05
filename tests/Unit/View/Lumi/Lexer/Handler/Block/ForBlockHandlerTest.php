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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndForBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ForBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class ForBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private ForBlockHandler $forHandler;
    private EndForBlockHandler $endForHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->forHandler = new ForBlockHandler();
        $this->endForHandler = new EndForBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testForHandlerDirectiveIsFor(): void
    {
        self::assertSame(
            'for',
            $this->forHandler->directive,
        );
    }

    public function testForHandlerLexWithValueOnlySyntaxEmitsForTokenWithoutKey(): void
    {
        $tokens = $this->forHandler->lex(
            startingLine: 1,
            expression: 'value in items',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertForToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'value',
            expectedOp2: null,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'items',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testForHandlerLexWithValueAndKeySyntaxEmitsForTokenWithBothBindings(): void
    {
        $tokens = $this->forHandler->lex(
            startingLine: 1,
            expression: 'value, key in items',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertForToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'value',
            expectedOp2: 'key',
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'items',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testForHandlerLexProducesLinearStreamForCompoundIterator(): void
    {
        $tokens = $this->forHandler->lex(
            startingLine: 1,
            expression: 'value in foo + bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \count($tokens));

        $this->assertForToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'value',
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testForHandlerLexThrowsWhenInKeywordIsMissing(): void
    {
        $this->expectException(LexerException::class);

        $this->forHandler->lex(
            startingLine: 1,
            expression: 'value items',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForHandlerLexThrowsWhenExpressionIsEmpty(): void
    {
        $this->expectException(LexerException::class);

        $this->forHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForHandlerLexThrowsWhenIteratorIsMissing(): void
    {
        $this->expectException(LexerException::class);

        $this->forHandler->lex(
            startingLine: 1,
            expression: 'value in ',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->forHandler->lex(
            startingLine: 7,
            expression: 'value in items',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertForToken(
            token: $tokens[0],
            expectedLine: 7,
            expectedOp1: 'value',
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 7,
        );
    }

    public function testEndForHandlerDirectiveIsEndFor(): void
    {
        self::assertSame(
            'endfor',
            $this->endForHandler->directive,
        );
    }

    public function testEndForHandlerLexEmitsSingleEndForToken(): void
    {
        $tokens = $this->endForHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertEndForToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testEndForHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->endForHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertEndForToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }
}
