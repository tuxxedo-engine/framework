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

enum CharacterSymbol implements SymbolInterface
{
    case LEFT_PARENTHESIS;
    case RIGHT_PARENTHESIS;
    case LEFT_SQUARE_BRACKET;
    case RIGHT_SQUARE_BRACKET;
    case COMMA;
    case DOT;
    case COLON;

    public function symbol(): string
    {
        return match ($this) {
            self::LEFT_PARENTHESIS => '(',
            self::RIGHT_PARENTHESIS => ')',
            self::LEFT_SQUARE_BRACKET => '[',
            self::RIGHT_SQUARE_BRACKET => ']',
            self::COMMA => ',',
            self::DOT => '.',
            self::COLON => ':',
        };
    }
}
