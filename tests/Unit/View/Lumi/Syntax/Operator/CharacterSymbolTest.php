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
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\Precedence;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;

class CharacterSymbolTest extends TestCase
{
    /**
     * @return \Generator<array{0: CharacterSymbol, 1: string}>
     */
    public static function provideSymbolVariants(): \Generator
    {
        yield [
            CharacterSymbol::LEFT_PARENTHESIS,
            '(',
        ];

        yield [
            CharacterSymbol::RIGHT_PARENTHESIS,
            ')',
        ];

        yield [
            CharacterSymbol::LEFT_SQUARE_BRACKET,
            '[',
        ];

        yield [
            CharacterSymbol::RIGHT_SQUARE_BRACKET,
            ']',
        ];

        yield [
            CharacterSymbol::COMMA,
            ',',
        ];

        yield [
            CharacterSymbol::DOT,
            '.',
        ];

        yield [
            CharacterSymbol::COLON,
            ':',
        ];
    }

    #[DataProvider('provideSymbolVariants')]
    public function testSymbolReturnsExpectedString(
        CharacterSymbol $symbol,
        string $expected,
    ): void {
        self::assertSame($expected, $symbol->symbol());
    }

    public function testAllReturnsEverySymbol(): void
    {
        self::assertSame(
            [
                '(',
                ')',
                '[',
                ']',
                ',',
                '.',
                ':',
            ],
            CharacterSymbol::all(),
        );
    }

    public function testAllMatchesCasesCount(): void
    {
        self::assertCount(
            \count(CharacterSymbol::cases()),
            CharacterSymbol::all(),
        );
    }

    /**
     * @return \Generator<array{0: CharacterSymbol, 1: Precedence}>
     */
    public static function providePrecedenceVariants(): \Generator
    {
        yield [
            CharacterSymbol::LEFT_PARENTHESIS,
            Precedence::ACCESS,
        ];

        yield [
            CharacterSymbol::LEFT_SQUARE_BRACKET,
            Precedence::ACCESS,
        ];

        yield [
            CharacterSymbol::DOT,
            Precedence::ACCESS,
        ];

        yield [
            CharacterSymbol::RIGHT_PARENTHESIS,
            Precedence::LOWEST,
        ];

        yield [
            CharacterSymbol::RIGHT_SQUARE_BRACKET,
            Precedence::LOWEST,
        ];

        yield [
            CharacterSymbol::COMMA,
            Precedence::LOWEST,
        ];

        yield [
            CharacterSymbol::COLON,
            Precedence::LOWEST,
        ];
    }

    #[DataProvider('providePrecedenceVariants')]
    public function testPrecedenceReturnsExpectedLevel(
        CharacterSymbol $symbol,
        Precedence $expected,
    ): void {
        self::assertSame($expected, $symbol->precedence());
    }

    #[DataProvider('provideSymbolVariants')]
    public function testIsReturnsTrueForMatchingCharacterToken(
        CharacterSymbol $symbol,
        string $expected,
    ): void {
        self::assertTrue(
            CharacterSymbol::is(
                new CharacterToken(
                    line: 1,
                    op1: $symbol->symbol(),
                ),
            ),
        );
    }

    public function testIsReturnsFalseForUnknownCharacter(): void
    {
        self::assertFalse(
            CharacterSymbol::is(
                new CharacterToken(
                    line: 1,
                    op1: '?',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForOperatorToken(): void
    {
        self::assertFalse(
            CharacterSymbol::is(
                new OperatorToken(
                    line: 1,
                    op1: '(',
                ),
            ),
        );
    }

    public function testIsReturnsFalseForIdentifierToken(): void
    {
        self::assertFalse(
            CharacterSymbol::is(
                new IdentifierToken(
                    line: 1,
                    op1: 'foo',
                ),
            ),
        );
    }

    #[DataProvider('provideSymbolVariants')]
    public function testFromRoundTripsMatchingCharacterToken(
        CharacterSymbol $symbol,
        string $expected,
    ): void {
        self::assertSame(
            $symbol,
            CharacterSymbol::from(
                new CharacterToken(
                    line: 1,
                    op1: $symbol->symbol(),
                ),
            ),
        );
    }

    public function testFromThrowsOnNonCharacterToken(): void
    {
        self::expectException(ParserException::class);

        CharacterSymbol::from(
            new OperatorToken(
                line: 1,
                op1: '(',
            ),
        );
    }

    public function testFromThrowsOnUnknownCharacter(): void
    {
        self::expectException(ParserException::class);

        CharacterSymbol::from(
            new CharacterToken(
                line: 1,
                op1: '?',
            ),
        );
    }
}
