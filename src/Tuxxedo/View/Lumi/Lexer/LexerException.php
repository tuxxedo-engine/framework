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

namespace Tuxxedo\View\Lumi\Lexer;

class LexerException extends \Exception
{
    public static function fromDuplicateSequence(
        string $sequence,
    ): self {
        return new self(
            message: \sprintf(
                'Duplicate sequence "%s" encountered in lexer configuration',
                $sequence,
            ),
        );
    }

    public static function fromFileNotFound(
        string $filename,
    ): self {
        return new self(
            message: \sprintf(
                'Template file "%s" could not be found',
                $filename,
            ),
        );
    }

    public static function fromEofReached(): self
    {
        return new self(
            message: 'Unexpected end of input reached while parsing',
        );
    }

    public static function fromSequenceNotFound(
        string $sequence,
    ): self {
        return new self(
            message: \sprintf(
                'Expected sequence "%s" not found in input stream',
                $sequence,
            ),
        );
    }

    public static function fromInvalidForSyntax(): self
    {
        return new self(
            message: 'Expected syntax: For loops must be constructed like {% for value[,key] in iterator %%}',
        );
    }

    public static function fromInvalidForeachSyntax(): self
    {
        return new self(
            message: 'Expected syntax: Foreach loops must be constructed like {% foreach iterator as [key =>]value %%}',
        );
    }

    public static function fromInvalidLoopDepth(): self
    {
        return new self(
            message: 'Invalid loop depth, must be a positive integer',
        );
    }

    public static function fromEmptyExpression(): self
    {
        return new self(
            message: 'Expressions cannot be empty',
        );
    }

    public static function fromInvalidQuotedString(
        string $quoteChar,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid quoted string, no ending %s character found',
                $quoteChar,
            ),
        );
    }

    public static function fromInvalidNumber(
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid numeric value: %s cannot be represented as a number',
                $value,
            ),
        );
    }

    public static function fromUnknownSymbol(
        string $symbol,
    ): self {
        return new self(
            message: \sprintf(
                'Unknown symbol "%s" encountered',
                $symbol,
            ),
        );
    }

    public static function fromTokenStreamEof(): self
    {
        return new self(
            message: 'Token stream has reached the end of stream unexpectedly',
        );
    }

    public static function fromUnexpectedToken(
        string $tokenName,
        string $expectedTokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token "%s" encountered in token stream, expected "%s"',
                $tokenName,
                $expectedTokenName,
            ),
        );
    }

    public static function fromMalformedToken(): self
    {
        return new self(
            message: 'Internal token is malformed',
        );
    }

    public static function fromUnexpectedTokenOp(
        string $operand,
        string $actualOperand,
        string $expectedOperand,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token operand for %s, expected "%s" but got "%s"',
                $operand,
                $expectedOperand,
                $actualOperand,
            ),
        );
    }
}
