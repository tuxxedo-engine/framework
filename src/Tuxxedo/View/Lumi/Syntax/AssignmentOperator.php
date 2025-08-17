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

enum AssignmentOperator implements SymbolInterface, OperatorAssociativityInterface
{
    case ADD;
    case SUBTRACT;
    case MULTIPLY;
    case DIVIDE;
    case MODULUS;
    case EXPONENTIATE;
    case BITWISE_AND;
    case BITWISE_OR;
    case BITWISE_XOR;
    case BITWISE_SHIFT_LEFT;
    case BITWISE_SHIFT_RIGHT;

    public function symbol(): string
    {
        return match ($this) {
            self::ADD => '+=',
            self::SUBTRACT => '-=',
            self::MULTIPLY => '*=',
            self::DIVIDE => '/=',
            self::MODULUS => '%=',
            self::EXPONENTIATE => '**=',
            self::BITWISE_AND => '&=',
            self::BITWISE_OR => '|=',
            self::BITWISE_XOR => '^=',
            self::BITWISE_SHIFT_LEFT => '<<=',
            self::BITWISE_SHIFT_RIGHT => '>>=',
        };
    }

    public function associativity(): OperatorAssociativity
    {
        return OperatorAssociativity::RIGHT;
    }
}
