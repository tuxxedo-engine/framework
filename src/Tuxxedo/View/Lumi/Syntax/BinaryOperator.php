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

enum BinaryOperator implements SymbolInterface, OperatorAssociativityInterface
{
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

    public function symbol(): string
    {
        return match ($this) {
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
