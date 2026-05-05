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
use Tuxxedo\View\Lumi\Lexer\Handler\Block\EndLumiBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\LumiBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerState;
use Tuxxedo\View\Lumi\Lexer\LexerStateFlag;

class LumiBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private LumiBlockHandler $lumiHandler;
    private EndLumiBlockHandler $endLumiHandler;
    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->lumiHandler = new LumiBlockHandler();
        $this->endLumiHandler = new EndLumiBlockHandler();
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    public function testLumiHandlerDirectiveIsLumi(): void
    {
        self::assertSame(
            'lumi',
            $this->lumiHandler->directive,
        );
    }

    public function testLumiHandlerLexReturnsNoTokens(): void
    {
        $tokens = $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertSame([], $tokens);
    }

    public function testLumiHandlerLexSetsTextAsRawFlag(): void
    {
        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertTrue(
            $this->state->hasFlag(LexerStateFlag::TEXT_AS_RAW),
        );
    }

    public function testLumiHandlerLexConfiguresEndSequenceAndDirective(): void
    {
        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertSame('{%', $this->state->textAsRawEndSequence);
        self::assertSame('endlumi', $this->state->textAsRawEndDirective);
    }

    public function testLumiHandlerLexStoresExpressionAsInternalBuffer(): void
    {
        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertSame('php', $this->state->internalBuffer);
    }

    public function testLumiHandlerLexThrowsWhenTextAsRawAlreadyActive(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);

        $this->expectException(LexerException::class);

        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testLumiHandlerLexThrowsWhenAnotherEndDirectiveIsExpected(): void
    {
        $this->state->setTextAsRawEndDirective('endsomethingelse');

        $this->expectException(LexerException::class);

        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );
    }

    public function testEndLumiHandlerDirectiveIsEndLumi(): void
    {
        self::assertSame(
            'endlumi',
            $this->endLumiHandler->directive,
        );
    }

    public function testEndLumiHandlerLexEmitsLumiTokenWithThemeAndSource(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php');
        $this->state->setTextAsRawBuffer('echo "hello";');

        $tokens = $this->endLumiHandler->lex(
            startingLine: 5,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertLumiToken(
            token: $tokens[0],
            expectedLine: 5,
            expectedOp1: 'php',
            expectedOp2: 'echo "hello";',
        );
    }

    public function testEndLumiHandlerLexClearsTextAsRawFlag(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php');

        $this->endLumiHandler->lex(
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

    public function testEndLumiHandlerLexClearsInternalBuffer(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php');

        $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertSame('', $this->state->internalBuffer);
    }

    public function testEndLumiHandlerLexClearsBufferAndSequenceAndDirective(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php');
        $this->state->setTextAsRawBuffer('content');
        $this->state->setTextAsRawEndSequence('{%');
        $this->state->setTextAsRawEndDirective('endlumi');

        $this->endLumiHandler->lex(
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

    public function testEndLumiHandlerLexThrowsWhenTextAsRawNotActive(): void
    {
        $this->expectException(LexerException::class);

        $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndLumiHandlerLexThrowsOnEmptyTheme(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('');

        $this->expectException(LexerException::class);

        $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndLumiHandlerLexThrowsOnThemeContainingDigits(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php5');

        $this->expectException(LexerException::class);

        $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndLumiHandlerLexThrowsOnThemeContainingSpecialCharacters(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('html-tag');

        $this->expectException(LexerException::class);

        $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );
    }

    public function testEndLumiHandlerLexAcceptsMixedCaseTheme(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('Html');
        $this->state->setTextAsRawBuffer('<div></div>');

        $tokens = $this->endLumiHandler->lex(
            startingLine: 1,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertLumiToken(
            token: $tokens[0],
            expectedLine: 1,
            expectedOp1: 'Html',
            expectedOp2: '<div></div>',
        );
    }

    public function testEndLumiHandlerLexPropagatesStartingLine(): void
    {
        $this->state->flag(LexerStateFlag::TEXT_AS_RAW);
        $this->state->setInternalBuffer('php');
        $this->state->setTextAsRawBuffer('echo;');

        $tokens = $this->endLumiHandler->lex(
            startingLine: 42,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        $this->assertLumiToken(
            token: $tokens[0],
            expectedLine: 42,
            expectedOp1: 'php',
            expectedOp2: 'echo;',
        );
    }

    public function testRoundTripFromLumiToEndLumiLeavesStateClean(): void
    {
        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertFalse($this->state->isClean());

        $this->endLumiHandler->lex(
            startingLine: 2,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertTrue($this->state->isClean());
    }

    public function testEndLumiHandlerEmitsSourceAccumulatedDuringLumiState(): void
    {
        $this->lumiHandler->lex(
            startingLine: 1,
            expression: 'php',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        $this->state->appendTextAsRawBuffer('echo ');
        $this->state->appendTextAsRawBuffer('"hello";');

        $tokens = $this->endLumiHandler->lex(
            startingLine: 3,
            expression: '',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::STANDALONE,
        );

        self::assertCount(1, $tokens);

        $this->assertLumiToken(
            token: $tokens[0],
            expectedLine: 3,
            expectedOp1: 'php',
            expectedOp2: 'echo "hello";',
        );
    }
}
