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

use Tuxxedo\View\Lumi\Token\TokenInterface;

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
     */
    public function current(): TokenInterface;

    public function peek(
        int $position = 1,
    ): ?TokenInterface;

    public function peekIs(
        string $tokenName,
    ): bool;

    /**
     * @throws LexerException
     */
    public function consume(): void;

    /**
     * @throws LexerException
     */
    public function expect(
        string $tokenName,
        ?string $op1 = null,
        ?string $op2 = null,
    ): TokenInterface;
}
