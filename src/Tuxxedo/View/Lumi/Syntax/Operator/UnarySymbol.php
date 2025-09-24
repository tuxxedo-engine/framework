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

namespace Tuxxedo\View\Lumi\Syntax\Operator;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

enum UnarySymbol implements SymbolInterface, ExpressionSymbolInterface
{
    case NOT;
    case NEGATE;
    case BITWISE_NOT;
    case INCREMENT_PRE;
    case INCREMENT_POST;
    case DECREMENT_PRE;
    case DECREMENT_POST;

    public static function all(): array
    {
        return \array_map(
            static fn (self $operator): string => $operator->symbol(),
            self::cases(),
        );
    }

    public static function is(
        TokenInterface $token,
        bool $post = false,
    ): bool {
        if ($token->type !== BuiltinTokenNames::OPERATOR->name) {
            return false;
        }

        if ($post) {
            return $token->op1 === self::INCREMENT_POST->symbol() || $token->op1 === self::DECREMENT_POST->symbol();
        }

        return \in_array($token->op1, self::all(), true);
    }

    public static function from(
        TokenInterface $token,
        bool $post = false,
    ): static {
        if ($token->type === BuiltinTokenNames::OPERATOR->name) {
            foreach (self::cases() as $operator) {
                if ($post && !$operator->isPost()) {
                    continue;
                }

                if ($token->op1 === $operator->symbol()) {
                    return $operator;
                }
            }
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $token->type,
            expectedTokenNames: self::all(),
            line: $token->line,
        );
    }

    public function symbol(): string
    {
        return match ($this) {
            self::NOT => '!',
            self::NEGATE => '-',
            self::BITWISE_NOT => '~',
            self::INCREMENT_PRE, self::INCREMENT_POST => '++',
            self::DECREMENT_PRE, self::DECREMENT_POST => '--',
        };
    }

    public function precedence(): Precedence
    {
        return match ($this) {
            self::INCREMENT_POST, self::DECREMENT_POST => Precedence::EXPONENTIATION,
            self::NOT, self::NEGATE, self::BITWISE_NOT, self::INCREMENT_PRE, self::DECREMENT_PRE => Precedence::TIGHT,
        };
    }

    public function isPost(): bool
    {
        return match ($this) {
            self::INCREMENT_POST, self::DECREMENT_POST => true,
            default => false,
        };
    }
}
