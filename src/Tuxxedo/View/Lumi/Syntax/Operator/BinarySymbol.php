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

enum BinarySymbol implements SymbolInterface, ExpressionSymbolInterface
{
    case CONCAT;
    case ADD;
    case SUBTRACT;
    case MULTIPLY;
    case DIVIDE;
    case MODULUS;
    case STRICT_EQUAL_IMPLICIT;
    case STRICT_EQUAL_EXPLICIT;
    case STRICT_NOT_EQUAL_IMPLICIT;
    case STRICT_NOT_EQUAL_EXPLICIT;
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
            self::STRICT_EQUAL_IMPLICIT => '==',
            self::STRICT_EQUAL_EXPLICIT => '===',
            self::STRICT_NOT_EQUAL_IMPLICIT => '!=',
            self::STRICT_NOT_EQUAL_EXPLICIT => '!==',
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

    public function bindingPower(): int
    {
        return match ($this) {
            self::NULL_SAFE_ACCESS => 80,
            self::EXPONENTIATE => 70,
            self::MULTIPLY, self::DIVIDE, self::MODULUS => 60,
            self::ADD, self::SUBTRACT, self::CONCAT => 50,
            self::BITWISE_SHIFT_LEFT, self::BITWISE_SHIFT_RIGHT => 45,
            self::BITWISE_AND => 40,
            self::BITWISE_XOR => 35,
            self::BITWISE_OR => 30,
            self::GREATER, self::LESS, self::GREATER_EQUAL, self::LESS_EQUAL => 25,
            self::STRICT_EQUAL_IMPLICIT, self::STRICT_EQUAL_EXPLICIT, self::STRICT_NOT_EQUAL_IMPLICIT, self::STRICT_NOT_EQUAL_EXPLICIT => 20,
            self::AND => 15,
            self::XOR => 14,
            self::OR => 13,
            self::NULL_COALESCE => 12,
        };
    }

    public function transform(): string
    {
        return match ($this) {
            self::STRICT_EQUAL_IMPLICIT => self::STRICT_EQUAL_EXPLICIT->symbol(),
            self::STRICT_NOT_EQUAL_IMPLICIT => self::STRICT_NOT_EQUAL_EXPLICIT->symbol(),
            default => $this->symbol(),
        };
    }
}
