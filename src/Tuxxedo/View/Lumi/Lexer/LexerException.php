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
                'Expected syntax: For loops must be constructed like {%% for value[,key] in iterator %%} on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidForeachSyntax(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Expected syntax: Foreach loops must be constructed like {%% foreach iterator as [key =>]value %%} on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidLoopDepth(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid loop depth, must be a positive integer on line %d',
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
                'Invalid quoted string, no ending %s character found on line %d',
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
                'Invalid numeric value: %s cannot be represented as a number %d',
                $value,
                $line,
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
            message: 'Token stream has reached the end of stream unexpectedly',
        );
    }

    public static function fromUnexpectedToken(
        string $tokenName,
        string $expectedTokenName,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token "%s" encountered in token stream, expected "%s" on line %d',
                $tokenName,
                $expectedTokenName,
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

    public static function fromUnexpectedTokenOp(
        string $operand,
        string $actualOperand,
        string $expectedOperand,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected token operand for %s, expected "%s" but got "%s" on line %d',
                $operand,
                $expectedOperand,
                $actualOperand,
                $line,
            ),
        );
    }

    public static function fromInvalidDeclare(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid declare syntax: A declare must have an assignment on line %d',
                $line,
            ),
        );
    }

    public static function fromInvalidDeclareLiteral(
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid declare syntax: The right operand must be a literal value on line %d',
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
                'Invalid layout file, must be a literal string value on line %d',
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
            message: 'Lexer state was left in an unclean state, possible end of sequence tag missing',
        );
    }
}
