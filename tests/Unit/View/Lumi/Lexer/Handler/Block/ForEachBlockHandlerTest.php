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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndForEachBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ForEachBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class ForEachBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private ForEachBlockHandler $forEachHandler;
    private EndForEachBlockHandler $endForEachHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->forEachHandler = new ForEachBlockHandler();
        $this->endForEachHandler = new EndForEachBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testForEachHandlerDirectiveIsForEach(): void
    {
        self::assertSame(
            'foreach',
            $this->forEachHandler->directive,
        );
    }

    public function testForEachHandlerLexWithValueOnlySyntaxEmitsForEachTokenWithoutKey(): void
    {
        $tokens = $this->forEachHandler->lex(
            startingLine: 1,
            expression: 'items as item',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertForEachToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'item',
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

    public function testForEachHandlerLexWithKeyValueSyntaxEmitsForEachTokenWithBothBindings(): void
    {
        $tokens = $this->forEachHandler->lex(
            startingLine: 1,
            expression: 'items as key => value',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertForEachToken(
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

    public function testForEachHandlerLexProducesLinearStreamForCompoundIterator(): void
    {
        $tokens = $this->forEachHandler->lex(
            startingLine: 1,
            expression: 'foo + bar as item',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \sizeof($tokens));

        $this->assertForEachToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'item',
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testForEachHandlerLexThrowsWhenAsKeywordIsMissing(): void
    {
        $this->expectException(LexerException::class);

        $this->forEachHandler->lex(
            startingLine: 1,
            expression: 'items item',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForEachHandlerLexThrowsWhenExpressionIsEmpty(): void
    {
        $this->expectException(LexerException::class);

        $this->forEachHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForEachHandlerLexThrowsWhenIteratorIsMissing(): void
    {
        $this->expectException(LexerException::class);

        $this->forEachHandler->lex(
            startingLine: 1,
            expression: 'as item',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testForEachHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->forEachHandler->lex(
            startingLine: 7,
            expression: 'items as item',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertForEachToken(
            token: $tokens[0],
            expectedLine: 7,
            expectedOp1: 'item',
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 7,
        );
    }

    public function testEndForEachHandlerDirectiveIsEndForEach(): void
    {
        self::assertSame(
            'endforeach',
            $this->endForEachHandler->directive,
        );
    }

    public function testEndForEachHandlerLexEmitsSingleEndForEachToken(): void
    {
        $tokens = $this->endForEachHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertEndForEachToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testEndForEachHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->endForEachHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertEndForEachToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }
}
