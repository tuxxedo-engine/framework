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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\AlwaysStandaloneInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndRawBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\RawBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Lexer\LexerStateFlag;
use Tuxxedo\View\Lumi\Syntax\TextContext;

class RawBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private RawBlockHandler $rawHandler;
    private EndRawBlockHandler $endRawHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->rawHandler = new RawBlockHandler();
        $this->endRawHandler = new EndRawBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testRawHandlerDirectiveIsRaw(): void
    {
        self::assertSame(
            'raw',
            $this->rawHandler->directive,
        );
    }

    public function testRawHandlerImplementsAlwaysStandaloneInterface(): void
    {
        self::assertInstanceOf(
            AlwaysStandaloneInterface::class,
            $this->rawHandler,
        );
    }

    public function testRawHandlerLexReturnsNoTokens(): void
    {
        $tokens = $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertSame([], $tokens);
    }

    public function testRawHandlerLexSetsTextAsRawFlag(): void
    {
        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertTrue(
            $this->state->hasFlag(LexerStateFlag::TEXT_AS_RAW),
        );
    }

    public function testRawHandlerLexConfiguresEndSequenceAndDirective(): void
    {
        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertSame('{%', $this->state->textAsRawEndSequence);
        self::assertSame('endraw', $this->state->textAsRawEndDirective);
    }

    public function testRawHandlerLexThrowsWhenTextAsRawAlreadyActive(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);

        $this->expectException(LexerException::class);

        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testRawHandlerLexThrowsWhenAnotherEndDirectiveIsExpected(): void
    {
        $this->state->setTextAsRawEndDirective('endsomethingelse');

        $this->expectException(LexerException::class);

        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndRawHandlerDirectiveIsEndRaw(): void
    {
        self::assertSame(
            'endraw',
            $this->endRawHandler->directive,
        );
    }

    public function testEndRawHandlerImplementsAlwaysStandaloneInterface(): void
    {
        self::assertInstanceOf(
            AlwaysStandaloneInterface::class,
            $this->endRawHandler,
        );
    }

    public function testEndRawHandlerLexEmitsTextTokenWithRawContext(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setTextAsRawBuffer('captured raw content');

        $tokens = $this->endRawHandler->lex(
            startingLine: 5,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertTextToken(
            token: $tokens[0],
            expectedLine: 5,
            expectedOp1: 'captured raw content',
            expectedOp2: TextContext::RAW->name,
        );
    }

    public function testEndRawHandlerLexClearsTextAsRawFlag(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);

        $this->endRawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertFalse(
            $this->state->hasFlag(LexerStateFlag::TEXT_AS_RAW),
        );
    }

    public function testEndRawHandlerLexClearsBufferAndSequenceAndDirective(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setTextAsRawBuffer('content');
        $this->state->setTextAsRawEndSequence('{%');
        $this->state->setTextAsRawEndDirective('endraw');

        $this->endRawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertSame('', $this->state->textAsRawBuffer);
        self::assertNull($this->state->textAsRawEndSequence);
        self::assertNull($this->state->textAsRawEndDirective);
    }

    public function testEndRawHandlerLexThrowsWhenTextAsRawNotActive(): void
    {
        $this->expectException(LexerException::class);

        $this->endRawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndRawHandlerLexPropagatesStartingLine(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setTextAsRawBuffer('payload');

        $tokens = $this->endRawHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertTextToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'payload',
            expectedOp2: TextContext::RAW->name,
        );
    }

    // ------------------------------------------------------------------
    // Round trip
    // ------------------------------------------------------------------

    public function testRoundTripFromRawToEndRawLeavesStateClean(): void
    {
        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertFalse($this->state->isClean());

        $this->endRawHandler->lex(
            startingLine: 2,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertTrue($this->state->isClean());
    }

    public function testEndRawHandlerEmitsBufferAccumulatedDuringRawState(): void
    {
        $this->rawHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->state->appendTextAsRawBuffer('first chunk ');
        $this->state->appendTextAsRawBuffer('second chunk');

        $tokens = $this->endRawHandler->lex(
            startingLine: 3,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertTextToken(
            token: $tokens[0],
            expectedLine: 3,
            expectedOp1: 'first chunk second chunk',
            expectedOp2: TextContext::RAW->name,
        );
    }
}
