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
     * @param string[] $expectedSymbols
     */
    public static function fromUnexpectedSymbolOneOf(
        string $symbol,
        array $expectedSymbols,
    ): self {
        return new self(
            message: \sprintf(
                'Syntax error: Unexpected symbol "%s", expected one of "%s"',
                $symbol,
                \join('", "', $expectedSymbols),
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
}
