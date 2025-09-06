<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface TokenStreamInterface
{
    public int $position {
        get;
    }

    /**
     * @var TokenInterface[]
     */
    public array $tokens {
        get;
    }

    /**
     * @phpstan-impure
     */
    public function eof(): bool;

    /**
     * @throws LexerException
     *
     * @phpstan-impure
     */
    public function current(): TokenInterface;

    /**
     * @phpstan-impure
     */
    public function currentIs(
        string $tokenName,
        ?string $op1 = null,
        ?string $op2 = null,
    ): bool;

    /**
     * @phpstan-impure
     */
    public function peek(
        int $position = 1,
    ): ?TokenInterface;

    /**
     * @phpstan-impure
     */
    public function peekIs(
        string $tokenName,
        ?string $op1 = null,
        ?string $op2 = null,
    ): bool;

    /**
     * @throws LexerException
     *
     * @phpstan-impure
     */
    public function consume(): TokenInterface;

    /**
     * @throws LexerException
     *
     * @phpstan-impure
     */
    public function expect(
        string $tokenName,
        ?string $op1 = null,
        ?string $op2 = null,
    ): TokenInterface;
}
