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

namespace Unit\View\Lumi\Lexer;

use Fixture\View\Lumi\Lexer\TokenStream\BarToken;
use Fixture\View\Lumi\Lexer\TokenStream\BazToken;
use Fixture\View\Lumi\Lexer\TokenStream\FooToken;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\TokenStream;

class TokenStreamTest extends TestCase
{
    public function testConstructorStoresTokens(): void
    {
        $token = new FooToken(line: 1);
        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame([$token], $stream->tokens);
    }

    public function testInitialPositionIsZero(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertSame(0, $stream->position);
    }

    public function testCloneResetsPosition(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new FooToken(line: 2),
            ],
        );

        $stream->consume();

        $cloned = clone $stream;

        self::assertSame(0, $cloned->position);
    }

    public function testEofReturnsTrueOnEmptyStream(): void
    {
        $stream = new TokenStream([]);

        self::assertTrue($stream->eof());
    }

    public function testEofReturnsFalseWithTokens(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertFalse($stream->eof());
    }

    public function testEofReturnsTrueAfterConsumingAll(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        $stream->consume();

        self::assertTrue($stream->eof());
    }

    public function testCurrentReturnsFirstToken(): void
    {
        $token = new FooToken(line: 1);
        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame($token, $stream->current());
    }

    public function testCurrentDoesNotAdvancePosition(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        $stream->current();

        self::assertSame(0, $stream->position);
    }

    public function testCurrentThrowsAtEof(): void
    {
        $stream = new TokenStream([]);

        self::expectException(LexerException::class);

        $stream->current();
    }

    public function testCurrentIsReturnsTrueForMatchingClass(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertTrue($stream->currentIs(FooToken::class));
    }

    public function testCurrentIsReturnsFalseForDifferentClass(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertFalse($stream->currentIs(BarToken::class));
    }

    public function testCurrentIsReturnsTrueWithMatchingOp1(): void
    {
        $stream = new TokenStream(
            [
                new BarToken(
                    line: 1,
                    op1: 'x',
                ),
            ],
        );

        self::assertTrue(
            $stream->currentIs(
                tokenClassName: BarToken::class,
                op1: 'x',
            ),
        );
    }

    public function testCurrentIsReturnsFalseWithNonMatchingOp1(): void
    {
        $stream = new TokenStream(
            [
                new BarToken(
                    line: 1,
                    op1: 'a',
                ),
            ],
        );

        self::assertFalse(
            $stream->currentIs(
                tokenClassName: BarToken::class,
                op1: 'x',
            ),
        );
    }

    public function testCurrentIsReturnsTrueWithMatchingOp1AndOp2(): void
    {
        $stream = new TokenStream(
            [
                new BazToken(
                    line: 1,
                    op1: 'x',
                    op2: 'y',
                ),
            ],
        );

        self::assertTrue(
            $stream->currentIs(
                tokenClassName: BazToken::class,
                op1: 'x',
                op2: 'y',
            ),
        );
    }

    public function testCurrentIsReturnsFalseWithNonMatchingOp2(): void
    {
        $stream = new TokenStream(
            [
                new BazToken(
                    line: 1,
                    op1: 'x',
                    op2: 'a',
                ),
            ],
        );

        self::assertFalse(
            $stream->currentIs(
                tokenClassName: BazToken::class,
                op1: 'x',
                op2: 'y',
            ),
        );
    }

    public function testPeekReturnsNextToken(): void
    {
        $next = new FooToken(line: 2);
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                $next,
            ],
        );

        self::assertSame($next, $stream->peek());
    }

    public function testPeekDoesNotAdvancePosition(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new FooToken(line: 2),
            ],
        );

        $stream->peek();

        self::assertSame(0, $stream->position);
    }

    public function testPeekReturnsNullAtEof(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        $stream->consume();

        self::assertNull($stream->peek());
    }

    public function testPeekReturnsNullWhenBeyondBounds(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertNull($stream->peek());
    }

    public function testPeekWithExplicitPositionReturnsCorrectToken(): void
    {
        $third = new FooToken(line: 3);
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new FooToken(line: 2),
                $third,
            ],
        );

        self::assertSame($third, $stream->peek(position: 2));
    }

    public function testPeekIsReturnsTrueForMatchingClass(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BarToken(line: 2),
            ],
        );

        self::assertTrue($stream->peekIs(BarToken::class));
    }

    public function testPeekIsReturnsFalseForDifferentClass(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BarToken(line: 2),
            ],
        );

        self::assertFalse($stream->peekIs(BazToken::class));
    }

    public function testPeekIsReturnsFalseAtEof(): void
    {
        $stream = new TokenStream([]);

        self::assertFalse($stream->peekIs(FooToken::class));
    }

    public function testPeekIsReturnsFalseWhenBeyondBounds(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::assertFalse($stream->peekIs(FooToken::class));
    }

    public function testPeekIsReturnsTrueWithMatchingOp1(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BarToken(
                    line: 2,
                    op1: 'x',
                ),
            ],
        );

        self::assertTrue(
            $stream->peekIs(
                tokenClassName: BarToken::class,
                op1: 'x',
            ),
        );
    }

    public function testPeekIsReturnsFalseWithNonMatchingOp1(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BarToken(
                    line: 2,
                    op1: 'a',
                ),
            ],
        );

        self::assertFalse(
            $stream->peekIs(
                tokenClassName: BarToken::class,
                op1: 'x',
            ),
        );
    }

    public function testPeekIsReturnsTrueWithMatchingOp1AndOp2(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BazToken(
                    line: 2,
                    op1: 'x',
                    op2: 'y',
                ),
            ],
        );

        self::assertTrue(
            $stream->peekIs(
                tokenClassName: BazToken::class,
                op1: 'x',
                op2: 'y',
            ),
        );
    }

    public function testPeekIsReturnsFalseWithNonMatchingOp2(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
                new BazToken(
                    line: 2,
                    op1: 'x',
                    op2: 'a',
                ),
            ],
        );

        self::assertFalse(
            $stream->peekIs(
                tokenClassName: BazToken::class,
                op1: 'x',
                op2: 'y',
            ),
        );
    }

    public function testConsumeReturnsToken(): void
    {
        $token = new FooToken(line: 1);
        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame($token, $stream->consume());
    }

    public function testConsumeAdvancesPosition(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        $stream->consume();

        self::assertSame(1, $stream->position);
    }

    public function testConsumeThrowsAtEof(): void
    {
        $stream = new TokenStream([]);

        self::expectException(LexerException::class);

        $stream->consume();
    }

    public function testExpectReturnsTokenOnClassMatch(): void
    {
        $token = new FooToken(line: 1);
        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame($token, $stream->expect(FooToken::class));
    }

    public function testExpectAdvancesPosition(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        $stream->expect(FooToken::class);

        self::assertSame(1, $stream->position);
    }

    public function testExpectWithOp1ReturnsToken(): void
    {
        $token = new BarToken(
            line: 1,
            op1: 'x',
        );

        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame(
            $token,
            $stream->expect(
                tokenClassName: BarToken::class,
                op1: 'x',
            ),
        );
    }

    public function testExpectWithOp1AndOp2ReturnsToken(): void
    {
        $token = new BazToken(
            line: 1,
            op1: 'x',
            op2: 'y',
        );

        $stream = new TokenStream(
            [
                $token,
            ],
        );

        self::assertSame(
            $token,
            $stream->expect(
                tokenClassName: BazToken::class,
                op1: 'x',
                op2: 'y',
            ),
        );
    }

    public function testExpectThrowsOnClassMismatch(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(BarToken::class);
    }

    public function testExpectThrowsMalformedTokenWhenOp1IsNull(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(
            tokenClassName: FooToken::class,
            op1: 'x',
        );
    }

    public function testExpectThrowsUnexpectedOpWhenOp1Mismatches(): void
    {
        $stream = new TokenStream(
            [
                new BarToken(
                    line: 1,
                    op1: 'a',
                ),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(
            tokenClassName: BarToken::class,
            op1: 'x',
        );
    }

    public function testExpectThrowsMalformedTokenWhenOp1NullOnOp2Check(): void
    {
        $stream = new TokenStream(
            [
                new FooToken(line: 1),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(
            tokenClassName: FooToken::class,
            op2: 'x',
        );
    }

    public function testExpectThrowsMalformedTokenWhenOp2IsNull(): void
    {
        $stream = new TokenStream(
            [
                new BarToken(
                    line: 1,
                    op1: 'a',
                ),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(
            tokenClassName: BarToken::class,
            op2: 'x',
        );
    }

    public function testExpectThrowsUnexpectedOpWhenOp2Mismatches(): void
    {
        $stream = new TokenStream(
            [
                new BazToken(
                    line: 1,
                    op1: 'a',
                    op2: 'b',
                ),
            ],
        );

        self::expectException(LexerException::class);

        $stream->expect(
            tokenClassName: BazToken::class,
            op1: 'a',
            op2: 'x',
        );
    }
}
