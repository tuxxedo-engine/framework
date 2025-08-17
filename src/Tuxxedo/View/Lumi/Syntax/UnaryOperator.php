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

enum UnaryOperator implements SymbolInterface, OperatorAssociativityInterface
{
    case NOT;
    case NEGATE;
    case BITWISE_NOT;
    case INCREMENT_PRE;
    case INCREMENT_POST;
    case DECREMENT_PRE;
    case DECREMENT_POST;

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

    public function associativity(): OperatorAssociativity
    {
        return match ($this) {
            self::INCREMENT_POST, self::DECREMENT_POST => OperatorAssociativity::LEFT,
            default => OperatorAssociativity::RIGHT,
        };
    }
}
