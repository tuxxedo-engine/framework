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
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

enum AssignmentSymbol implements SymbolInterface
{
    case ASSIGN;
    case CONCAT;
    case NULL_ASSIGN;
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
        if (!$token instanceof OperatorToken) {
            return false;
        }

        return \in_array($token->op1, self::all(), true);
    }

    public static function from(
        TokenInterface $token,
    ): static {
        if ($token instanceof OperatorToken) {
            foreach (self::cases() as $operator) {
                if ($token->op1 === $operator->symbol()) {
                    return $operator;
                }
            }
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $token::name(),
            expectedTokenNames: self::all(),
            line: $token->line,
        );
    }

    public function symbol(): string
    {
        return match ($this) {
            self::ASSIGN => '=',
            self::CONCAT => '~=',
            self::NULL_ASSIGN => '??=',
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

    public function transform(): string
    {
        return match ($this) {
            self::CONCAT => '.=',
            default => $this->symbol(),
        };
    }
}
