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

use Tuxxedo\View\Lumi\Syntax\Token\AssignToken;
use Tuxxedo\View\Lumi\Syntax\Token\BlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\BreakToken;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\ContinueToken;
use Tuxxedo\View\Lumi\Syntax\Token\DeclareToken;
use Tuxxedo\View\Lumi\Syntax\Token\DoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EchoToken;
use Tuxxedo\View\Lumi\Syntax\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\ElseToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndBlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForEachToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndWhileToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForEachToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\IfToken;
use Tuxxedo\View\Lumi\Syntax\Token\IncludeToken;
use Tuxxedo\View\Lumi\Syntax\Token\LayoutToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\LumiToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;
use Tuxxedo\View\Lumi\Syntax\Token\WhileToken;

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

    private function assertLumiToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
        string $expectedOp2,
    ): void {
        self::assertInstanceOf(
            LumiToken::class,
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

    private function assertIfToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            IfToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertElseIfToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            ElseIfToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertElseToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            ElseToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertEndIfToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndIfToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertAssignToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            AssignToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertIncludeToken(
        TokenInterface $token,
        int $expectedLine,
        ?string $expectedOp1 = null,
    ): void {
        self::assertInstanceOf(
            IncludeToken::class,
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

    private function assertDeclareToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            DeclareToken::class,
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

    private function assertBlockToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            BlockToken::class,
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

    private function assertEndBlockToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndBlockToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertLayoutToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
    ): void {
        self::assertInstanceOf(
            LayoutToken::class,
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

    private function assertForToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
        ?string $expectedOp2 = null,
    ): void {
        self::assertInstanceOf(
            ForToken::class,
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

    private function assertEndForToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndForToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertForEachToken(
        TokenInterface $token,
        int $expectedLine,
        string $expectedOp1,
        ?string $expectedOp2 = null,
    ): void {
        self::assertInstanceOf(
            ForEachToken::class,
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

    private function assertEndForEachToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndForEachToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertBreakToken(
        TokenInterface $token,
        int $expectedLine,
        ?string $expectedOp1 = null,
    ): void {
        self::assertInstanceOf(
            BreakToken::class,
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

    private function assertContinueToken(
        TokenInterface $token,
        int $expectedLine,
        ?string $expectedOp1 = null,
    ): void {
        self::assertInstanceOf(
            ContinueToken::class,
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

    private function assertDoToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            DoToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertWhileToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            WhileToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }

    private function assertEndWhileToken(
        TokenInterface $token,
        int $expectedLine,
    ): void {
        self::assertInstanceOf(
            EndWhileToken::class,
            $token,
        );

        self::assertSame(
            $expectedLine,
            $token->line,
        );
    }
}
