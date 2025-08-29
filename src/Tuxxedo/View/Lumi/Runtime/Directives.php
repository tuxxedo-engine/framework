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

namespace Tuxxedo\View\Lumi\Runtime;

use Tuxxedo\View\ViewException;

readonly class Directives implements DirectivesInterface
{
    /**
     * @param array<string, string|int|float|bool|null> $directives
     */
    public function __construct(
        public array $directives,
    ) {
    }

    public function has(
        string $directive,
    ): bool {
        return \array_key_exists($directive, $this->directives);
    }

    public function asString(
        string $directive,
    ): string {
        if (!\array_key_exists($directive, $this->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        } elseif (!\is_string($this->directives[$directive])) {
            throw ViewException::fromInvalidDirectiveType(
                directive: $directive,
                type: \gettype($this->directives[$directive]),
                expectedType: 'string',
            );
        }

        return $this->directives[$directive];
    }

    public function asInt(
        string $directive,
    ): int {
        if (!\array_key_exists($directive, $this->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        } elseif (!\is_int($this->directives[$directive])) {
            throw ViewException::fromInvalidDirectiveType(
                directive: $directive,
                type: \gettype($this->directives[$directive]),
                expectedType: 'int',
            );
        }

        return $this->directives[$directive];
    }

    public function asFloat(
        string $directive,
    ): float {
        if (!\array_key_exists($directive, $this->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        } elseif (!\is_float($this->directives[$directive])) {
            throw ViewException::fromInvalidDirectiveType(
                directive: $directive,
                type: \gettype($this->directives[$directive]),
                expectedType: 'float',
            );
        }

        return $this->directives[$directive];
    }

    public function asBool(
        string $directive,
    ): bool {
        if (!\array_key_exists($directive, $this->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        } elseif (!\is_bool($this->directives[$directive])) {
            throw ViewException::fromInvalidDirectiveType(
                directive: $directive,
                type: \gettype($this->directives[$directive]),
                expectedType: 'bool',
            );
        }

        return $this->directives[$directive];
    }

    public function isNull(
        string $directive,
    ): bool {
        if (!\array_key_exists($directive, $this->directives)) {
            throw ViewException::fromInvalidDirective(
                directive: $directive,
            );
        }

        return $this->directives[$directive] === null;
    }
}
