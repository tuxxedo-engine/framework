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
     * @param positive-int $amount
     *
     * @throws LexerException
     */
    public function consume(
        int $amount = 1,
    ): void;
}
