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

enum BinaryOperator implements SymbolInterface, OperatorInterface
{
    case CONCAT;
    case ADD;
    case SUBTRACT;
    case MULTIPLY;
    case DIVIDE;
    case MODULUS;
    case EQUAL;
    case STRICT_EQUAL;
    case NOT_EQUAL;
    case STRICT_NOT_EQUAL;
    case GREATER;
    case LESS;
    case GREATER_EQUAL;
    case LESS_EQUAL;
    case AND;
    case OR;
    case XOR;
    case EXPONENTIATE;
    case BITWISE_AND;
    case BITWISE_OR;
    case BITWISE_XOR;
    case BITWISE_SHIFT_LEFT;
    case BITWISE_SHIFT_RIGHT;
    case NULL_COALESCE;
    case NULL_SAFE_ACCESS;

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
            self::CONCAT => '~',
            self::ADD => '+',
            self::SUBTRACT => '-',
            self::MULTIPLY => '*',
            self::DIVIDE => '/',
            self::MODULUS => '%',
            self::EQUAL => '==',
            self::STRICT_EQUAL => '===',
            self::NOT_EQUAL => '!=',
            self::STRICT_NOT_EQUAL => '!==',
            self::GREATER => '>',
            self::LESS => '<',
            self::GREATER_EQUAL => '>=',
            self::LESS_EQUAL => '<=',
            self::AND => '&&',
            self::OR => '||',
            self::XOR => '^^',
            self::EXPONENTIATE => '**',
            self::BITWISE_AND => '&',
            self::BITWISE_OR => '|',
            self::BITWISE_XOR => '^',
            self::BITWISE_SHIFT_LEFT => '<<',
            self::BITWISE_SHIFT_RIGHT => '>>',
            self::NULL_COALESCE => '??',
            self::NULL_SAFE_ACCESS => '?.',
        };
    }

    public function precedence(): int
    {
        return match ($this) {
            self::OR => 2,
            self::AND => 3,
            self::EQUAL, self::NOT_EQUAL, self::STRICT_EQUAL, self::STRICT_NOT_EQUAL, self::NULL_COALESCE => 4,
            self::LESS, self::GREATER, self::LESS_EQUAL, self::GREATER_EQUAL => 5,
            self::ADD, self::SUBTRACT => 6,
            self::MULTIPLY, self::DIVIDE, self::MODULUS => 7,
            self::EXPONENTIATE => 8,
            default => 9,
        };
    }

    public function associativity(): OperatorAssociativity
    {
        return match ($this) {
            self::EXPONENTIATE => OperatorAssociativity::RIGHT,
            default => OperatorAssociativity::LEFT,
        };
    }
}
