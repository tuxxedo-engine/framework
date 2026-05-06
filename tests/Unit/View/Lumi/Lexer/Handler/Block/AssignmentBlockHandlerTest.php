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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Lexer\TokenAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\LetBlockHandler;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\SetBlockHandler;
use Tuxxedo\View\Lumi\Lexer\LexerState;

class AssignmentBlockHandlerTest extends TestCase
{
    use TokenAssertionsTrait;

    private ExpressionLexer $expressionLexer;
    private LexerState $state;

    protected function setUp(): void
    {
        $this->expressionLexer = new ExpressionLexer();
        $this->state = new LexerState();
    }

    /**
     * @return \Generator<array{0: string, 1: SetBlockHandler|LetBlockHandler}>
     */
    public static function handlerDataProvider(): \Generator
    {
        yield [
            'set',
            new SetBlockHandler(),
        ];

        yield [
            'let',
            new LetBlockHandler(),
        ];
    }

    #[DataProvider('handlerDataProvider')]
    public function testSetHandlerDirectiveIsSet(
        string $verb,
        SetBlockHandler|LetBlockHandler $handler,
    ): void {
        self::assertSame(
            $verb,
            $handler->directive,
        );
    }

    #[DataProvider('handlerDataProvider')]
    public function testSetHandlerLexWrapsAssignmentWithAssignAndEndTokens(
        string $verb,
        SetBlockHandler|LetBlockHandler $handler,
    ): void {
        $tokens = $handler->lex(
            startingLine: 1,
            expression: "var = 'value'",
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertGreaterThan(3, \count($tokens));

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertEndToken(
            token: $tokens[\count($tokens) - 1],
            expectedLine: 1,
        );
    }

    #[DataProvider('handlerDataProvider')]
    public function testSetHandlerLexWrapsSingleIdentifierWithAssignAndEndTokens(
        string $verb,
        SetBlockHandler|LetBlockHandler $handler,
    ): void {
        $tokens = $handler->lex(
            startingLine: 1,
            expression: 'var',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 1,
        );

        $this->assertIdentifierToken(
            token: $tokens[1],
            expectedOp1: 'var',
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 1,
        );
    }

    #[DataProvider('handlerDataProvider')]
    public function testSetHandlerLexPropagatesStartingLineToWrapperTokens(
        string $verb,
        SetBlockHandler|LetBlockHandler $handler,
    ): void {
        $tokens = $handler->lex(
            startingLine: 7,
            expression: 'var',
            expressionLexer: $this->expressionLexer,
            state: $this->state,
            blockState: BlockHandlerState::EXPRESSIVE,
        );

        self::assertCount(3, $tokens);

        $this->assertAssignToken(
            token: $tokens[0],
            expectedLine: 7,
        );

        $this->assertEndToken(
            token: $tokens[2],
            expectedLine: 7,
        );
    }
}
