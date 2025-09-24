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
            line: $token->line,
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

    public function precedence(): Precedence
    {
        return match ($this) {
            self::NULL_SAFE_ACCESS => Precedence::ACCESS,
            self::EXPONENTIATE => Precedence::EXPONENTIATION,
            self::MULTIPLY, self::DIVIDE, self::MODULUS => Precedence::TIGHT,
            self::ADD, self::SUBTRACT, self::CONCAT => Precedence::ADDITIVE,
            self::BITWISE_SHIFT_LEFT, self::BITWISE_SHIFT_RIGHT => Precedence::SHIFT,
            self::BITWISE_AND => Precedence::BITWISE_AND,
            self::BITWISE_XOR => Precedence::BITWISE_XOR,
            self::BITWISE_OR => Precedence::BITWISE_OR,
            self::GREATER, self::LESS, self::GREATER_EQUAL, self::LESS_EQUAL => Precedence::COMPARISON,
            self::STRICT_EQUAL_IMPLICIT, self::STRICT_EQUAL_EXPLICIT, self::STRICT_NOT_EQUAL_IMPLICIT, self::STRICT_NOT_EQUAL_EXPLICIT => Precedence::EQUALITY,
            self::AND => Precedence::LOGICAL_AND,
            self::XOR => Precedence::LOGICAL_XOR,
            self::OR => Precedence::LOGICAL_OR,
            self::NULL_COALESCE => Precedence::NULL_COALESCE,
        };
    }

    public function associativity(): Associativity
    {
        return match ($this) {
            self::EXPONENTIATE, self::NULL_COALESCE => Associativity::RIGHT,
            default => Associativity::LEFT,
        };
    }

    public function transform(): string
    {
        return match ($this) {
            self::STRICT_EQUAL_IMPLICIT => self::STRICT_EQUAL_EXPLICIT->symbol(),
            self::STRICT_NOT_EQUAL_IMPLICIT => self::STRICT_NOT_EQUAL_EXPLICIT->symbol(),
            self::CONCAT => '.',
            self::XOR => 'xor',
            self::BITWISE_XOR => '^',
            default => $this->symbol(),
        };
    }
}
