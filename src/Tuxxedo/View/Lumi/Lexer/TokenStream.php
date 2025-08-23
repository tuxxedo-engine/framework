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
        ?string $op1 = null,
        ?string $op2 = null,
    ): TokenInterface {
        $current = $this->current();

        if ($current->type !== $tokenName) {
            throw LexerException::fromUnexpectedToken(
                tokenName: $current->type,
                expectedTokenName: $tokenName,
            );
        } elseif ($op1 !== null && $current->op1 !== $op1) {
            if ($current->op1 === null) {
                throw LexerException::fromMalformedToken();
            }

            throw LexerException::fromUnexpectedTokenOp(
                operand: 'op1',
                actualOperand: $current->op1,
                expectedOperand: $op1,
            );
        } elseif ($op2 !== null && $current->op2 !== $op2) {
            if ($current->op1 === null || $current->op2 === null) {
                throw LexerException::fromMalformedToken();
            }

            throw LexerException::fromUnexpectedTokenOp(
                operand: 'op2',
                actualOperand: $current->op2,
                expectedOperand: $op2,
            );
        }

        $this->consume();

        return $current;
    }
}
