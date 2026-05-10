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
use Tuxxedo\View\Lumi\Syntax\Operator\Associativity;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\Precedence;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;

class BinarySymbolTest extends TestCase
{
    /**
     * @return \Generator<array{0: BinarySymbol, 1: string}>
     */
    public static function provideSymbolVariants(): \Generator
    {
        yield [
            BinarySymbol::CONCAT,
            '~',
        ];

        yield [
            BinarySymbol::ADD,
            '+',
        ];

        yield [
            BinarySymbol::SUBTRACT,
            '-',
        ];

        yield [
            BinarySymbol::MULTIPLY,
            '*',
        ];

        yield [
            BinarySymbol::DIVIDE,
            '/',
        ];

        yield [
            BinarySymbol::MODULUS,
            '%',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_IMPLICIT,
            '==',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_EXPLICIT,
            '===',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT,
            '!=',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
            '!==',
        ];

        yield [
            BinarySymbol::GREATER,
            '>',
        ];

        yield [
            BinarySymbol::LESS,
            '<',
        ];

        yield [
            BinarySymbol::GREATER_EQUAL,
            '>=',
        ];

        yield [
            BinarySymbol::LESS_EQUAL,
            '<=',
        ];

        yield [
            BinarySymbol::AND,
            '&&',
        ];

        yield [
            BinarySymbol::OR,
            '||',
        ];

        yield [
            BinarySymbol::XOR,
            '^^',
        ];

        yield [
            BinarySymbol::EXPONENTIATE,
            '**',
        ];

        yield [
            BinarySymbol::BITWISE_AND,
            '&',
        ];

        yield [
            BinarySymbol::BITWISE_OR,
            '|',
        ];

        yield [
            BinarySymbol::BITWISE_XOR,
            '^',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_LEFT,
            '<<',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_RIGHT,
            '>>',
        ];

        yield [
            BinarySymbol::NULL_COALESCE,
            '??',
        ];

        yield [
            BinarySymbol::NULL_SAFE_ACCESS,
            '?.',
        ];
    }

    #[DataProvider('provideSymbolVariants')]
    public function testSymbolReturnsExpectedString(
        BinarySymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->symbol());
    }

    public function testAllMatchesCasesCount(): void
    {
        self::assertCount(
            \sizeof(BinarySymbol::cases()),
            BinarySymbol::all(),
        );
    }

    public function testAllContainsEverySymbolValue(): void
    {
        $expected = \array_map(
            static fn (BinarySymbol $symbol): string => $symbol->symbol(),
            BinarySymbol::cases(),
        );

        self::assertSame($expected, BinarySymbol::all());
    }

    /**
     * @return \Generator<array{0: BinarySymbol, 1: string}>
     */
    public static function provideCompileVariants(): \Generator
    {
        yield [
            BinarySymbol::CONCAT,
            '.',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_IMPLICIT,
            '===',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT,
            '!==',
        ];

        yield [
            BinarySymbol::XOR,
            'xor',
        ];

        yield [
            BinarySymbol::BITWISE_XOR,
            '^',
        ];

        yield [
            BinarySymbol::ADD,
            '+',
        ];

        yield [
            BinarySymbol::SUBTRACT,
            '-',
        ];

        yield [
            BinarySymbol::MULTIPLY,
            '*',
        ];

        yield [
            BinarySymbol::DIVIDE,
            '/',
        ];

        yield [
            BinarySymbol::MODULUS,
            '%',
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_EXPLICIT,
            '===',
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
            '!==',
        ];

        yield [
            BinarySymbol::GREATER,
            '>',
        ];

        yield [
            BinarySymbol::LESS,
            '<',
        ];

        yield [
            BinarySymbol::GREATER_EQUAL,
            '>=',
        ];

        yield [
            BinarySymbol::LESS_EQUAL,
            '<=',
        ];

        yield [
            BinarySymbol::AND,
            '&&',
        ];

        yield [
            BinarySymbol::OR,
            '||',
        ];

        yield [
            BinarySymbol::EXPONENTIATE,
            '**',
        ];

        yield [
            BinarySymbol::BITWISE_AND,
            '&',
        ];

        yield [
            BinarySymbol::BITWISE_OR,
            '|',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_LEFT,
            '<<',
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_RIGHT,
            '>>',
        ];

        yield [
            BinarySymbol::NULL_COALESCE,
            '??',
        ];

        yield [
            BinarySymbol::NULL_SAFE_ACCESS,
            '?.',
        ];
    }

    #[DataProvider('provideCompileVariants')]
    public function testCompileReturnsExpectedString(
        BinarySymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->compile());
    }

    /**
     * @return \Generator<array{0: BinarySymbol, 1: Precedence}>
     */
    public static function providePrecedenceVariants(): \Generator
    {
        yield [
            BinarySymbol::NULL_SAFE_ACCESS,
            Precedence::ACCESS,
        ];

        yield [
            BinarySymbol::EXPONENTIATE,
            Precedence::EXPONENTIATION,
        ];

        yield [
            BinarySymbol::MULTIPLY,
            Precedence::TIGHT,
        ];

        yield [
            BinarySymbol::DIVIDE,
            Precedence::TIGHT,
        ];

        yield [
            BinarySymbol::MODULUS,
            Precedence::TIGHT,
        ];

        yield [
            BinarySymbol::ADD,
            Precedence::ADDITIVE,
        ];

        yield [
            BinarySymbol::SUBTRACT,
            Precedence::ADDITIVE,
        ];

        yield [
            BinarySymbol::CONCAT,
            Precedence::ADDITIVE,
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_LEFT,
            Precedence::SHIFT,
        ];

        yield [
            BinarySymbol::BITWISE_SHIFT_RIGHT,
            Precedence::SHIFT,
        ];

        yield [
            BinarySymbol::BITWISE_AND,
            Precedence::BITWISE_AND,
        ];

        yield [
            BinarySymbol::BITWISE_XOR,
            Precedence::BITWISE_XOR,
        ];

        yield [
            BinarySymbol::BITWISE_OR,
            Precedence::BITWISE_OR,
        ];

        yield [
            BinarySymbol::GREATER,
            Precedence::COMPARISON,
        ];

        yield [
            BinarySymbol::LESS,
            Precedence::COMPARISON,
        ];

        yield [
            BinarySymbol::GREATER_EQUAL,
            Precedence::COMPARISON,
        ];

        yield [
            BinarySymbol::LESS_EQUAL,
            Precedence::COMPARISON,
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_IMPLICIT,
            Precedence::EQUALITY,
        ];

        yield [
            BinarySymbol::STRICT_EQUAL_EXPLICIT,
            Precedence::EQUALITY,
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT,
            Precedence::EQUALITY,
        ];

        yield [
            BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
            Precedence::EQUALITY,
        ];

        yield [
            BinarySymbol::AND,
            Precedence::LOGICAL_AND,
        ];

        yield [
            BinarySymbol::XOR,
            Precedence::LOGICAL_XOR,
        ];

        yield [
            BinarySymbol::OR,
            Precedence::LOGICAL_OR,
        ];

        yield [
            BinarySymbol::NULL_COALESCE,
            Precedence::NULL_COALESCE,
        ];
    }

    #[DataProvider('providePrecedenceVariants')]
    public function testPrecedenceReturnsExpectedLevel(
        BinarySymbol $symbol,
        Precedence $expected,
    ): void {
        self::assertSame($expected, $symbol->precedence());
    }

    /**
     * @return \Generator<array{0: BinarySymbol, 1: Associativity}>
     */
    public static function provideAssociativityVariants(): \Generator
    {
        yield [
            BinarySymbol::EXPONENTIATE,
            Associativity::RIGHT,
        ];

        yield [
            BinarySymbol::NULL_COALESCE,
            Associativity::RIGHT,
        ];

        yield [
            BinarySymbol::ADD,
            Associativity::LEFT,
        ];

        yield [
            BinarySymbol::CONCAT,
            Associativity::LEFT,
        ];

        yield [
            BinarySymbol::AND,
            Associativity::LEFT,
        ];

        yield [
            BinarySymbol::OR,
            Associativity::LEFT,
        ];

        yield [
            BinarySymbol::BITWISE_OR,
            Associativity::LEFT,
        ];
    }

    #[DataProvider('provideAssociativityVariants')]
    public function testAssociativityReturnsExpectedDirection(
        BinarySymbol $symbol,
        Associativity $expected,
    ): void {
        self::assertSame($expected, $symbol->associativity());
    }

    #[DataProvider('provideSymbolVariants')]
    public function testIsReturnsTrueForMatchingOperatorToken(
        BinarySymbol $symbol,
        string $expected,
    ): void {
        self::assertTrue(
            BinarySymbol::is(
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
            BinarySymbol::is(
                new CharacterToken(
                    line: 1,
                    op1: '+',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForIdentifierToken(): void
    {
        self::assertFalse(
            BinarySymbol::is(
                new IdentifierToken(
                    line: 1,
                    op1: '+',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForUnknownOperator(): void
    {
        self::assertFalse(
            BinarySymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '@@@',
                ),
            ),
        );
    }

    #[DataProvider('provideSymbolVariants')]
    public function testFromRoundTripsMatchingOperatorToken(
        BinarySymbol $symbol,
        string $expected,
    ): void {
        self::assertSame(
            $symbol,
            BinarySymbol::from(
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

        BinarySymbol::from(
            new CharacterToken(
                line: 1,
                op1: '+',
            ),
        );
    }

    public function testFromThrowsOnUnknownOperator(): void
    {
        self::expectException(ParserException::class);

        BinarySymbol::from(
            new OperatorToken(
                line: 1,
                op1: '@@@',
            ),
        );
    }
}
