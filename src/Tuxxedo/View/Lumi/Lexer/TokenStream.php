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

class TokenStream implements TokenStreamInterface
{
    public private(set) int $position = 0;

    /**
     * @param TokenInterface[] $tokens
     */
    public function __construct(
        public readonly array $tokens,
    ) {
    }

    public function eof(): bool
    {
        return $this->position === \sizeof($this->tokens);
    }

    public function current(): TokenInterface
    {
        if ($this->eof()) {
            throw LexerException::fromTokenStreamEof();
        }

        return $this->tokens[$this->position];
    }

    public function peek(
        int $position = 1,
    ): ?TokenInterface {
        if ($this->eof() || ($this->position + $position) > \sizeof($this->tokens)) {
            return null;
        }

        return $this->tokens[$this->position + $position];
    }

    public function peekIs(
        string $tokenName,
    ): bool {
        return $this->peek()?->type === $tokenName;
    }

    public function consume(
        int $amount = 1,
    ): void {
        $slots = \sizeof($this->tokens) - $this->position;

        if ($amount > $slots) {
            throw LexerException::fromTokenStreamEof();
        }

        $this->position += $amount;
    }

    public function expect(
        string $tokenName,
    ): TokenInterface {
        $current = $this->current();

        if ($current->type !== $tokenName) {
            throw LexerException::fromUnexpectedToken(
                tokenName: $current->type,
                expectedTokenName: $tokenName,
            );
        }

        $this->consume();

        return $current;
    }
}
