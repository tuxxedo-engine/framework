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

class ParserException extends \Exception
{
    public static function fromUnexpectedToken(
        string $tokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s"',
                $tokenName,
            ),
        );
    }

    public static function fromMalformedToken(): self
    {
        return new self(
            message: 'Internal token is malformed',
        );
    }

    public static function fromUnexpectedTokenWithExpects(
        string $tokenName,
        string $expectedTokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s", expected "%s"',
                $tokenName,
                $expectedTokenName,
            ),
        );
    }

    /**
     * @param string[] $expectedTokenNames
     */
    public static function fromUnexpectedTokenWithExpectsOneOf(
        string $tokenName,
        array $expectedTokenNames,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected token "%s", expected one of "%s"',
                $tokenName,
                \join('", "', $expectedTokenNames),
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

    public static function fromUnexpectedLoopExit(): self
    {
        return new self(
            message: 'Cannot exit loop: no active loop context found',
        );
    }

    public static function fromUnexpectedConditionExit(): self
    {
        return new self(
            message: 'Cannot exit condition: no active conditional context found',
        );
    }

    public static function fromUnexpectedGroupingExit(): self
    {
        return new self(
            message: 'Cannot exit grouping: no active grouping context found',
        );
    }

    public static function fromUnexpectedNamedGroupingExit(
        string $name,
        string $expectedName,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot exit grouping for "%s": Invalid sequence, expected to exit "%s"',
                $name,
                $expectedName,
            ),
        );
    }

    public static function fromUnexpectedNodeStackExit(): self
    {
        return new self(
            message: 'Cannot pop node stack, as the stack is empty',
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

    /**
     * @param string[] $expectedTokenNativeTypes
     */
    public static function fromUnexpectedTokenNativeType(
        string $tokenNativeType,
        array $expectedTokenNativeTypes,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token native type "%s", expected one of "%s"',
                $tokenNativeType,
                \join('", "', $expectedTokenNativeTypes),
            ),
        );
    }

    // @todo Remove
    public static function fromNotImplemented(
        string $feature,
    ): self {
        return new self(
            message: \sprintf(
                'The following feature is not implemented: %s',
                $feature,
            ),
        );
    }

    public static function fromEmptyExpression(): self
    {
        return new self(
            message: 'Expressions cannot be empty',
        );
    }

    public static function fromUnexpectedContinueOutsideLoop(): self
    {
        return new self(
            message: 'Continue cannot be used outside of loops',
        );
    }

    public static function fromUnexpectedContinueOutOfBounds(
        int $level,
        int $maxLevel,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot continue %d, current loop depth is %d',
                $level,
                $maxLevel,
            ),
        );
    }

    public static function fromUnexpectedBreakOutsideLoop(): self
    {
        return new self(
            message: 'Break cannot be used outside of loops',
        );
    }

    public static function fromUnexpectedBreakOutOfBounds(
        int $level,
        int $maxLevel,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot break %d, current loop depth is %d',
                $level,
                $maxLevel,
            ),
        );
    }
}
