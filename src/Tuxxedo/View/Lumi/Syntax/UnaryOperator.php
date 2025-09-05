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

namespace Tuxxedo\View\Lumi\Syntax;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

enum UnaryOperator implements SymbolInterface, OperatorInterface
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
    ): bool {
        if ($token->type !== BuiltinTokenNames::OPERATOR->name) {
            return false;
        }

        return \in_array($token->op1, self::all(), true);
    }

    public static function from(
        TokenInterface $token,
    ): static {
        if ($token->type === BuiltinTokenNames::OPERATOR->name) {
            foreach (self::cases() as $operator) {
                if ($token->op1 === $operator->symbol()) {
                    return $operator;
                }
            }
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $token->type,
            expectedTokenNames: self::all(),
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

    public function precedence(): int
    {
        return match ($this) {
            self::INCREMENT_POST, self::DECREMENT_POST => 15,
            self::INCREMENT_PRE, self::DECREMENT_PRE, self::NOT, self::NEGATE, self::BITWISE_NOT => 14,
        };
    }

    public function associativity(): OperatorAssociativity
    {
        return match ($this) {
            self::INCREMENT_POST, self::DECREMENT_POST => OperatorAssociativity::LEFT,
            default => OperatorAssociativity::RIGHT,
        };
    }
}
