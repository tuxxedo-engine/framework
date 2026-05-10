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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ElseBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\ElseIfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndIfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\IfBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class IfBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private IfBlockHandler $ifHandler;
    private ElseIfBlockHandler $elseIfHandler;
    private ElseBlockHandler $elseHandler;
    private EndIfBlockHandler $endIfHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->ifHandler = new IfBlockHandler();
        $this->elseIfHandler = new ElseIfBlockHandler();
        $this->elseHandler = new ElseBlockHandler();
        $this->endIfHandler = new EndIfBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testIfHandlerDirectiveIsIf(): void
    {
        self::assertSame(
            'if',
            $this->ifHandler->directive,
        );
    }

    public function testIfHandlerLexWrapsExpressionWithIfAndEndTokens(): void
    {
        $tokens = $this->ifHandler->lex(
            startingLine: 1,
            expression: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertIfToken(
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

    public function testIfHandlerLexProducesLinearStreamForCompoundExpression(): void
    {
        $tokens = $this->ifHandler->lex(
            startingLine: 1,
            expression: 'foo == bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \sizeof($tokens));

        $this->assertIfToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testIfHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->ifHandler->lex(
            startingLine: 7,
            expression: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertIfToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }

    public function testElseIfHandlerDirectiveIsElseIf(): void
    {
        self::assertSame(
            'elseif',
            $this->elseIfHandler->directive,
        );
    }

    public function testElseIfHandlerLexWrapsExpressionWithElseIfAndEndTokens(): void
    {
        $tokens = $this->elseIfHandler->lex(
            startingLine: 1,
            expression: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertElseIfToken(
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

    public function testElseIfHandlerLexProducesLinearStreamForCompoundExpression(): void
    {
        $tokens = $this->elseIfHandler->lex(
            startingLine: 1,
            expression: 'foo == bar',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \sizeof($tokens));

        $this->assertElseIfToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\sizeof($tokens) - 1],
            expectedLine: 1,
        );
    }

    public function testElseIfHandlerLexPropagatesStartingLineToWrapperTokens(): void
    {
        $tokens = $this->elseIfHandler->lex(
            startingLine: 7,
            expression: 'foo',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertElseIfToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }

    public function testElseHandlerDirectiveIsElse(): void
    {
        self::assertSame(
            'else',
            $this->elseHandler->directive,
        );
    }

    public function testElseHandlerLexEmitsSingleElseToken(): void
    {
        $tokens = $this->elseHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertElseToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testElseHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->elseHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertElseToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }

    public function testEndIfHandlerDirectiveIsEndIf(): void
    {
        self::assertSame(
            'endif',
            $this->endIfHandler->directive,
        );
    }

    public function testEndIfHandlerLexEmitsSingleEndIfToken(): void
    {
        $tokens = $this->endIfHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertEndIfToken(
            token: $tokens[0],
            expectedLine: 1,
        );
    }

    public function testEndIfHandlerLexPropagatesStartingLine(): void
    {
        $tokens = $this->endIfHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertEndIfToken(
            token: $tokens[0],
            expectedLine: 42,
        );
    }
}
