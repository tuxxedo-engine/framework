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

namespace Tuxxedo\View\Lumi\Parser;

use Tuxxedo\View\Lumi\LumiException;

class ParserException extends LumiException
{
    public static function fromUnexpectedToken(
        string $tokenName,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s" on line %d',
                $tokenName,
                $line,
            ),
        );
    }

    public static function fromMalformedToken(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Internal token is malformed on line %d',
                $line,
            ),
        );
    }

    /**
     * @param string[] $expectedTokenNames
     */
    public static function fromUnexpectedTokenWithExpectsOneOf(
        string $tokenName,
        array $expectedTokenNames,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s", expected one of "%s" on line %d',
                $tokenName,
                \join('", "', $expectedTokenNames),
                $line,
            ),
        );
    }

    public static function fromTokenStreamEof(): self
    {
        return new self(
            message: 'Token stream has reached the end of stream unexpectedly',
        );
    }

    public static function fromNodeStreamEof(): self
    {
        return new self(
            message: 'Node stream has reached the end of stream unexpectedly',
        );
    }

    // @todo Support line numbers?
    public static function fromUnexpectedLoopExit(): self
    {
        return new self(
            message: 'Cannot exit loop: no active loop context found',
        );
    }

    // @todo Support line numbers?
    public static function fromUnexpectedConditionExit(): self
    {
        return new self(
            message: 'Cannot exit condition: no active conditional context found',
        );
    }

    // @todo Support line numbers?
    public static function fromUnexpectedStackExit(): self
    {
        return new self(
            message: 'Cannot pop state stack, as the stack is empty',
        );
    }

    public static function fromMissingStateKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'State key "%s" is not defined',
                $key,
            ),
        );
    }

    public static function fromUnexpectedStateType(
        string $key,
        string $type,
        string $expectedType,
    ): self {
        return new self(
            message: \sprintf(
                'Expected a %s value for key "%s", but received %s',
                $expectedType,
                $key,
                $type,
            ),
        );
    }

    public static function fromEmptyExpression(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Expressions cannot be empty on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedContinueOutsideLoop(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Continue cannot be used outside of loops on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedContinueOutOfBounds(
        int $level,
        int $maxLevel,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot continue %d, current loop depth is %d on line %d',
                $level,
                $maxLevel,
                $line,
            ),
        );
    }

    public static function fromUnexpectedBreakOutsideLoop(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Break cannot be used outside of loops on line %d',
                $line,
            ),
        );
    }

    public static function fromUnexpectedBreakOutOfBounds(
        int $level,
        int $maxLevel,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot break %d, current loop depth is %d on line %d',
                $level,
                $maxLevel,
                $line,
            ),
        );
    }

    public static function fromExpressionNotIterable(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'For or foreach iterator expression must be iterable on line %d',
                $line,
            ),
        );
    }
}
