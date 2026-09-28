<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\LumiException;

class LexerException extends LumiException
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

    public static function fromUnexpectedSequenceFound(
        string $sequence,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected sequence "%s" found in input stream on line %d',
                $sequence,
                $line,
            ),
        );
    }

    public static function fromInvalidForSyntax(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid for-loop syntax: expected {%% for value[, key] in iterator %%} on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidForeachSyntax(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid foreach-loop syntax: expected {%% foreach iterator as [key =>] value %%} on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidLoopDepth(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Loop depth must be a positive integer on line %d',
                $line,
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

    public static function fromInvalidQuotedString(
        string $quoteChar,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unterminated string literal, expected closing %s character on line %d',
                $quoteChar,
                $line,
            ),
        );
    }

    public static function fromInvalidNumber(
        string $value,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid numeric value %s cannot be represented as a number%s',
                self::formatQuoted($value),
                self::formatLocation($line),
            ),
        );
    }

    public static function fromUnknownSymbol(
        string $symbol,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unknown symbol "%s" encountered on line %d',
                $symbol,
                $line,
            ),
        );
    }

    public static function fromTokenStreamEof(): self
    {
        return new self(
            message: 'Token stream ended unexpectedly',
        );
    }

    public static function fromUnexpectedToken(
        string $tokenName,
        string $expectedTokenName,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token in token stream, %s%s',
                self::formatExpectedGot(
                    expected: self::formatShortClass($expectedTokenName),
                    got: self::formatShortClass($tokenName),
                ),
                self::formatLocation($line),
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

    public static function fromUnexpectedTokenOp(
        string $operand,
        string $actualOperand,
        string $expectedOperand,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token operand for %s, %s%s',
                $operand,
                self::formatExpectedGot(
                    expected: $expectedOperand,
                    got: $actualOperand,
                ),
                self::formatLocation($line),
            ),
        );
    }

    public static function fromInvalidDeclare(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid declare syntax: declare statements must have an assignment on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidDeclareLiteral(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid declare syntax: right operand must be a literal value on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidBlockName(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid block name, must be a string on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidLayoutName(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid layout file, expected a literal string on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidTextAsRawEnd(
        int $line,
        string $tokenName,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid "%s" token encountered on line %d',
                $tokenName,
                $line,
            ),
        );
    }

    public static function fromUncleanLexerState(): self
    {
        return new self(
            message: 'Lexer finished in an unclean state, possible missing end-of-sequence tag',
        );
    }

    public static function fromInvalidLumiTheme(
        int $line,
        string $theme,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid highlight theme "%s" encountered on line %d',
                $theme,
                $line,
            ),
        );
    }

    public static function fromEnteringInvalidState(
        int $line,
        LexerStateFlag $stateFlag,
    ): self {
        return new self(
            message: \sprintf(
                'Attempting to enter an invalid state for "%s", possible double start sequence encountered on line %d',
                $stateFlag->name,
                $line,
            ),
        );
    }
}
