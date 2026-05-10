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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\DoBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndWhileBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\WhileBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class WhileBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private DoBlockHandler $doHandler;
    private WhileBlockHandler $whileHandler;
    private EndWhileBlockHandler $endWhileHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->doHandler = new DoBlockHandler();
        $this->whileHandler = new WhileBlockHandler();
        $this->endWhileHandler = new EndWhileBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testDoHandlerDirectiveIsDo(): void
    {
        self::assertSame(
            'do',
            $this->doHandler->directive,
        );
    }

    public function testDoHandlerLexEmitsSingleDoToken(): void
    {
        $tokens = $this->doHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertDoToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testDoHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->doHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertDoToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }

    public function testWhileHandlerDirectiveIsWhile(): void
    {
        self::assertSame(
            'while',
            $this->whileHandler->directive,
        );
    }

    public function testWhileHandlerLexWrapsConditionWithWhileAndEndTokens(): void
    {
        $tokens = $this->whileHandler->lex(
            startingLine: 1,
            expression: 'condition',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertWhileToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'condition',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    public function testWhileHandlerLexProducesLinearStreamForCompoundCondition(): void
    {
        $tokens = $this->whileHandler->lex(
            startingLine: 1,
            expression: 'foo == bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \sizeof($tokens));

        $this->assertWhileToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testWhileHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->whileHandler->lex(
            startingLine: 7,
            expression: 'condition',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->assertWhileToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 7,
        );
    }

    public function testEndWhileHandlerDirectiveIsEndWhile(): void
    {
        self::assertSame(
            'endwhile',
            $this->endWhileHandler->directive,
        );
    }

    public function testEndWhileHandlerLexEmitsSingleEndWhileToken(): void
    {
        $tokens = $this->endWhileHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertEndWhileToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testEndWhileHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->endWhileHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertEndWhileToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }
}
