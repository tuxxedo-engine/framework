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

namespace Support\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\EchoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

trait TokenAssertionsTrait
{
    private function assertLiteralToken(
        TokenInterface $token,
        string $expectedOp1,
        string $expectedOp2,
    ): void {
        self::assertInstanceOf(
            LiteralToken::class,
            $token,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );

        self::assertSame(
            $expectedOp2,
            $token->op2,
        );
    }

    private function assertIdentifierToken(
        TokenInterface $token,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            IdentifierToken::class,
            $token,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );
    }

    private function assertOperatorToken(
        TokenInterface $token,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            OperatorToken::class,
            $token,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );
    }

    private function assertCharacterToken(
        TokenInterface $token,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            CharacterToken::class,
            $token,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );
    }

    private function assertCommentToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            CommentToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );
    }

    private function assertEchoToken(
        TokenInterface $token,
        int $expectedLine,
        ?string $expectedOp1 = null,
    ): void {
        self::assertInstanceOf(
            EchoToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );
    }

    private function assertEndToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertTextToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
        ?string $expectedOp2 = null,
    ): void {
        self::assertInstanceOf(
            TextToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );

        self::assertSame(
            $expectedOp1,
            $token->op1,
        );

        self::assertSame(
            $expectedOp2,
            $token->op2,
        );
    }
}
