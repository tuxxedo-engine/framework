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
}
