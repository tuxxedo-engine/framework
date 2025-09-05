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

enum CharacterSymbol implements SymbolInterface
{
    case LEFT_PARENTHESIS;
    case RIGHT_PARENTHESIS;
    case LEFT_SQUARE_BRACKET;
    case RIGHT_SQUARE_BRACKET;
    case COMMA;
    case DOT;
    case COLON;

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
        if ($token->type !== BuiltinTokenNames::CHARACTER->name) {
            return false;
        }

        return \in_array($token->op1, self::all(), true);
    }

    public static function from(
        TokenInterface $token,
    ): static {
        if ($token->type === BuiltinTokenNames::CHARACTER->name) {
            foreach (self::cases() as $character) {
                if ($token->op1 === $character->symbol()) {
                    return $character;
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
