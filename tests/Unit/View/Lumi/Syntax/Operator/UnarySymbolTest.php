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

namespace Unit\View\Lumi\Syntax\Operator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Operator\Precedence;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;

class UnarySymbolTest extends TestCase
{
    /**
     * @return \Generator<array{0: UnarySymbol, 1: string}>
     */
    public static function provideSymbolVariants(): \Generator
    {
        yield [
            UnarySymbol::NOT,
            '!',
        ];

        yield [
            UnarySymbol::NEGATE,
            '-',
        ];

        yield [
            UnarySymbol::BITWISE_NOT,
            '~',
        ];

        yield [
            UnarySymbol::INCREMENT_PRE,
            '++',
        ];

        yield [
            UnarySymbol::INCREMENT_POST,
            '++',
        ];

        yield [
            UnarySymbol::DECREMENT_PRE,
            '--',
        ];

        yield [
            UnarySymbol::DECREMENT_POST,
            '--',
        ];
    }

    #[DataProvider('provideSymbolVariants')]
    public function testSymbolReturnsExpectedString(
        UnarySymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->symbol());
    }

    public function testAllMatchesCasesCount(): void
    {
        self::assertCount(
            \sizeof(UnarySymbol::cases()),
            UnarySymbol::all(),
        );
    }

    public function testAllReturnsEverySymbol(): void
    {
        $expected = \array_map(
            static fn (UnarySymbol $symbol): string => $symbol->symbol(),
            UnarySymbol::cases(),
        );

        self::assertSame($expected, UnarySymbol::all());
    }

    /**
     * @return \Generator<array{0: UnarySymbol, 1: Precedence}>
     */
    public static function providePrecedenceVariants(): \Generator
    {
        yield [
            UnarySymbol::INCREMENT_POST,
            Precedence::EXPONENTIATION,
        ];

        yield [
            UnarySymbol::DECREMENT_POST,
            Precedence::EXPONENTIATION,
        ];

        yield [
            UnarySymbol::NOT,
            Precedence::TIGHT,
        ];

        yield [
            UnarySymbol::NEGATE,
            Precedence::TIGHT,
        ];

        yield [
            UnarySymbol::BITWISE_NOT,
            Precedence::TIGHT,
        ];

        yield [
            UnarySymbol::INCREMENT_PRE,
            Precedence::TIGHT,
        ];

        yield [
            UnarySymbol::DECREMENT_PRE,
            Precedence::TIGHT,
        ];
    }

    #[DataProvider('providePrecedenceVariants')]
    public function testPrecedenceReturnsExpectedLevel(
        UnarySymbol $symbol,
        Precedence $expected,
    ): void {
        self::assertSame($expected, $symbol->precedence());
    }

    /**
     * @return \Generator<array{0: UnarySymbol, 1: bool}>
     */
    public static function provideIsPostVariants(): \Generator
    {
        yield [
            UnarySymbol::INCREMENT_POST,
            true,
        ];

        yield [
            UnarySymbol::DECREMENT_POST,
            true,
        ];

        yield [
            UnarySymbol::NOT,
            false,
        ];

        yield [
            UnarySymbol::NEGATE,
            false,
        ];

        yield [
            UnarySymbol::BITWISE_NOT,
            false,
        ];

        yield [
            UnarySymbol::INCREMENT_PRE,
            false,
        ];

        yield [
            UnarySymbol::DECREMENT_PRE,
            false,
        ];
    }

    #[DataProvider('provideIsPostVariants')]
    public function testIsPostReturnsExpectedFlag(
        UnarySymbol $symbol,
        bool $expected,
    ): void {
        self::assertSame($expected, $symbol->isPost());
    }

    #[DataProvider('provideSymbolVariants')]
    public function testIsReturnsTrueForMatchingOperatorToken(
        UnarySymbol $symbol,
        string $expected,
    ): void {
        self::assertTrue(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: $symbol->symbol(),
                ),
            ),
        );
    }

    public function testIsReturnsFalseForUnknownOperator(): void
    {
        self::assertFalse(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '@@@',
                ),
            ),
        );
    }

    public function testIsWithPostFlagReturnsTrueForIncrement(): void
    {
        self::assertTrue(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '++',
                ),
                post: true,
            ),
        );
    }

    public function testIsWithPostFlagReturnsTrueForDecrement(): void
    {
        self::assertTrue(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '--',
                ),
                post: true,
            ),
        );
    }

    public function testIsWithPostFlagReturnsFalseForNonPostOperators(): void
    {
        self::assertFalse(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '!',
                ),
                post: true,
            ),
        );

        self::assertFalse(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '-',
                ),
                post: true,
            ),
        );

        self::assertFalse(
            UnarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '~',
                ),
                post: true,
            ),
        );
    }

    public function testFromIncrementWithoutPostFlagReturnsPreVariant(): void
    {
        self::assertSame(
            UnarySymbol::INCREMENT_PRE,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '++',
                ),
            ),
        );
    }

    public function testFromDecrementWithoutPostFlagReturnsPreVariant(): void
    {
        self::assertSame(
            UnarySymbol::DECREMENT_PRE,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '--',
                ),
            ),
        );
    }

    public function testFromIncrementWithPostFlagReturnsPostVariant(): void
    {
        self::assertSame(
            UnarySymbol::INCREMENT_POST,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '++',
                ),
                post: true,
            ),
        );
    }

    public function testFromDecrementWithPostFlagReturnsPostVariant(): void
    {
        self::assertSame(
            UnarySymbol::DECREMENT_POST,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '--',
                ),
                post: true,
            ),
        );
    }

    public function testFromUniqueSymbolWithoutPostFlagRoundTripsToMatchingCase(): void
    {
        self::assertSame(
            UnarySymbol::NOT,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '!',
                ),
            ),
        );

        self::assertSame(
            UnarySymbol::NEGATE,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '-',
                ),
            ),
        );

        self::assertSame(
            UnarySymbol::BITWISE_NOT,
            UnarySymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: '~',
                ),
            ),
        );
    }

    public function testFromWithPostFlagThrowsForNonPostSymbols(): void
    {
        self::expectException(ParserException::class);

        UnarySymbol::from(
            new OperatorToken(
                line: 1,
                op1: '!',
            ),
            post: true,
        );
    }

    public function testFromThrowsOnNonOperatorToken(): void
    {
        self::expectException(ParserException::class);

        UnarySymbol::from(
            new CharacterToken(
                line: 1,
                op1: '!',
            ),
        );
    }

    public function testFromThrowsOnIdentifierToken(): void
    {
        self::expectException(ParserException::class);

        UnarySymbol::from(
            new IdentifierToken(
                line: 1,
                op1: '!',
            ),
        );
    }

    public function testFromThrowsOnUnknownOperator(): void
    {
        self::expectException(ParserException::class);

        UnarySymbol::from(
            new OperatorToken(
                line: 1,
                op1: '@@@',
            ),
        );
    }
}
