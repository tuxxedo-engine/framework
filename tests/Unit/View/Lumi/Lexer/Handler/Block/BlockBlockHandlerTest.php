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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class BlockBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private BlockBlockHandler $blockHandler;
    private EndBlockHandler $endBlockHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->blockHandler = new BlockBlockHandler();
        $this->endBlockHandler = new EndBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testBlockHandlerDirectiveIsBlock(): void
    {
        self::assertSame(
            'block',
            $this->blockHandler->directive,
        );
    }

    public function testBlockHandlerLexWithIdentifierEmitsBlockToken(): void
    {
        $tokens = $this->blockHandler->lex(
            startingLine: 1,
            expression: 'sidebar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(1, $tokens);

        $this->assertBlockToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'sidebar',
        );
    }

    public function testBlockHandlerLexThrowsWhenExpressionIsEmpty(): void
    {
        $this->expectException(LexerException::class);

        $this->blockHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testBlockHandlerLexThrowsWhenExpressionIsStringLiteral(): void
    {
        $this->expectException(LexerException::class);

        $this->blockHandler->lex(
            startingLine: 1,
            expression: "'sidebar'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testBlockHandlerLexThrowsWhenExpressionIsCompound(): void
    {
        $this->expectException(LexerException::class);

        $this->blockHandler->lex(
            startingLine: 1,
            expression: 'foo + bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testBlockHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->blockHandler->lex(
            startingLine: 42,
            expression: 'sidebar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertBlockToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'sidebar',
        );
    }

    public function testEndBlockHandlerDirectiveIsEndBlock(): void
    {
        self::assertSame(
            'endblock',
            $this->endBlockHandler->directive,
        );
    }

    public function testEndBlockHandlerLexEmitsSingleEndBlockToken(): void
    {
        $tokens = $this->endBlockHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertEndBlockToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testEndBlockHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->endBlockHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertEndBlockToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }
}
