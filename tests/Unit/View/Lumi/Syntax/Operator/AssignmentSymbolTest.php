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
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;

class AssignmentSymbolTest extends TestCase
{
    /**
     * @return \Generator<array{0: AssignmentSymbol, 1: string}>
     */
    public static function provideSymbolVariants(): \Generator
    {
        yield [
            AssignmentSymbol::ASSIGN,
            '=',
        ];

        yield [
            AssignmentSymbol::CONCAT,
            '~=',
        ];

        yield [
            AssignmentSymbol::NULL_ASSIGN,
            '??=',
        ];

        yield [
            AssignmentSymbol::ADD,
            '+=',
        ];

        yield [
            AssignmentSymbol::SUBTRACT,
            '-=',
        ];

        yield [
            AssignmentSymbol::MULTIPLY,
            '*=',
        ];

        yield [
            AssignmentSymbol::DIVIDE,
            '/=',
        ];

        yield [
            AssignmentSymbol::MODULUS,
            '%=',
        ];

        yield [
            AssignmentSymbol::EXPONENTIATE,
            '**=',
        ];

        yield [
            AssignmentSymbol::BITWISE_AND,
            '&=',
        ];

        yield [
            AssignmentSymbol::BITWISE_OR,
            '|=',
        ];

        yield [
            AssignmentSymbol::BITWISE_XOR,
            '^=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_LEFT,
            '<<=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_RIGHT,
            '>>=',
        ];
    }

    #[DataProvider('provideSymbolVariants')]
    public function testSymbolReturnsExpectedString(
        AssignmentSymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->symbol());
    }

    public function testAllMatchesCasesCount(): void
    {
        self::assertCount(
            \count(AssignmentSymbol::cases()),
            AssignmentSymbol::all(),
        );
    }

    public function testAllReturnsEverySymbol(): void
    {
        $expected = \array_map(
            static fn (AssignmentSymbol $symbol): string => $symbol->symbol(),
            AssignmentSymbol::cases(),
        );

        self::assertSame($expected, AssignmentSymbol::all());
    }

    /**
     * @return \Generator<array{0: AssignmentSymbol, 1: string}>
     */
    public static function provideCompileVariants(): \Generator
    {
        yield [
            AssignmentSymbol::CONCAT,
            '.=',
        ];

        yield [
            AssignmentSymbol::ASSIGN,
            '=',
        ];

        yield [
            AssignmentSymbol::NULL_ASSIGN,
            '??=',
        ];

        yield [
            AssignmentSymbol::ADD,
            '+=',
        ];

        yield [
            AssignmentSymbol::SUBTRACT,
            '-=',
        ];

        yield [
            AssignmentSymbol::MULTIPLY,
            '*=',
        ];

        yield [
            AssignmentSymbol::DIVIDE,
            '/=',
        ];

        yield [
            AssignmentSymbol::MODULUS,
            '%=',
        ];

        yield [
            AssignmentSymbol::EXPONENTIATE,
            '**=',
        ];

        yield [
            AssignmentSymbol::BITWISE_AND,
            '&=',
        ];

        yield [
            AssignmentSymbol::BITWISE_OR,
            '|=',
        ];

        yield [
            AssignmentSymbol::BITWISE_XOR,
            '^=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_LEFT,
            '<<=',
        ];

        yield [
            AssignmentSymbol::BITWISE_SHIFT_RIGHT,
            '>>=',
        ];
    }

    #[DataProvider('provideCompileVariants')]
    public function testCompileReturnsExpectedString(
        AssignmentSymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->compile());
    }

    #[DataProvider('provideSymbolVariants')]
    public function testIsReturnsTrueForMatchingOperatorToken(
        AssignmentSymbol $symbol,
        string $expected,
    ): void {
        self::assertTrue(
            AssignmentSymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: $symbol->symbol(),
                ),
            ),
        );
    }

    public function testIsReturnsFalseForCharacterToken(): void
    {
        self::assertFalse(
            AssignmentSymbol::is(
                new CharacterToken(
                    line: 1,
                    op1: '=',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForIdentifierToken(): void
    {
        self::assertFalse(
            AssignmentSymbol::is(
                new IdentifierToken(
                    line: 1,
                    op1: '=',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForUnknownOperator(): void
    {
        self::assertFalse(
            AssignmentSymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '@@@=',
                ),
            ),
        );
    }

    #[DataProvider('provideSymbolVariants')]
    public function testFromRoundTripsMatchingOperatorToken(
        AssignmentSymbol $symbol,
        string $expected,
    ): void {
        self::assertSame(
            $symbol,
            AssignmentSymbol::from(
                new OperatorToken(
                    line: 1,
                    op1: $symbol->symbol(),
                ),
            ),
        );
    }

    public function testFromThrowsOnNonOperatorToken(): void
    {
        self::expectException(ParserException::class);

        AssignmentSymbol::from(
            new CharacterToken(
                line: 1,
                op1: '=',
            ),
        );
    }

    public function testFromThrowsOnIdentifierToken(): void
    {
        self::expectException(ParserException::class);

        AssignmentSymbol::from(
            new IdentifierToken(
                line: 1,
                op1: '=',
            ),
        );
    }

    public function testFromThrowsOnUnknownOperator(): void
    {
        self::expectException(ParserException::class);

        AssignmentSymbol::from(
            new OperatorToken(
                line: 1,
                op1: '@@@=',
            ),
        );
    }
}
