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

namespace Tuxxedo\Env;

class EnvException extends \Exception
{
    public static function fromDuplicateKey(
        string $file,
        int $line,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Duplicate key "%s" in "%s" on line %d',
                $key,
                $file,
                $line,
            ),
        );
    }

    public static function fromInvalidKey(
        string $file,
        int $line,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid key "%s" in "%s" on line %d',
                $key,
                $file,
                $line,
            ),
        );
    }

    public static function fromMissingEquals(
        string $file,
        int $line,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Expected "=" after key "%s" in "%s" on line %d',
                $key,
                $file,
                $line,
            ),
        );
    }

    public static function fromInterpolationContainsNewline(
        string $file,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Interpolation expression cannot contain a newline in "%s" on line %d',
                $file,
                $line,
            ),
        );
    }

    public static function fromUnterminatedInterpolation(
        string $file,
        int $line,
    ): self {
        return new self(
            message: \sprintf(
                'Unterminated interpolation expression in "%s" starting on line %d',
                $file,
                $line,
            ),
        );
    }

    public static function fromInvalidInterpolationVariable(
        string $file,
        int $line,
        string $name,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid interpolation variable name "%s" in "%s" on line %d',
                $name,
                $file,
                $line,
            ),
        );
    }

    public static function fromUnexpectedCharacter(
        string $file,
        int $line,
        string $character,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected character "%s" after value in "%s" on line %d',
                $character,
                $file,
                $line,
            ),
        );
    }

    public static function fromUnknownEscapeSequence(
        string $file,
        int $line,
        string $sequence,
    ): self {
        return new self(
            message: \sprintf(
                'Unknown escape sequence "\\%s" in "%s" on line %d',
                $sequence,
                $file,
                $line,
            ),
        );
    }

    public static function fromUnclosedQuote(
        string $file,
        int $line,
        string $quote,
    ): self {
        return new self(
            message: \sprintf(
                'Unclosed %s-quoted string in "%s" starting on line %d',
                $quote,
                $file,
                $line,
            ),
        );
    }

    public static function fromUnresolvedInterpolation(
        string $file,
        int $line,
        string $reference,
    ): self {
        return new self(
            message: \sprintf(
                'Unresolved interpolation "${%s}" in "%s" on line %d',
                $reference,
                $file,
                $line,
            ),
        );
    }

    public static function fromMissingKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Missing required environment variable "%s"',
                $key,
            ),
        );
    }

    public static function fromMissingFile(
        string $file,
    ): self {
        return new self(
            message: \sprintf(
                'Environment file does not exist: "%s"',
                $file,
            ),
        );
    }

    public static function fromInvalidCoercion(
        string $key,
        string $expectedType,
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot coerce environment variable "%s" to type "%s": value "%s" does not match',
                $key,
                $expectedType,
                $value,
            ),
        );
    }

    /**
     * @param class-string<\UnitEnum> $enum
     */
    public static function fromInvalidEnum(
        string $key,
        string $enum,
        string $value,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot resolve environment variable "%s" to enum "%s": value "%s" is not a valid case',
                $key,
                $enum,
                $value,
            ),
        );
    }

    public static function fromUnboundEnv(
        \Throwable $previous,
    ): self {
        return new self(
            message: 'EnvInterface is not bound on the container: pass an EnvInterface to ApplicationConfigurator or bind one manually before resolving services that use #[Env]',
            previous: $previous,
        );
    }

    public static function fromUnsupportedParameterType(
        string $key,
        ?string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Cannot resolve environment variable "%s" into parameter type "%s": only string, int, bool, float, and \UnitEnum subclasses are supported',
                $key,
                $type ?? 'mixed',
            ),
        );
    }
}
