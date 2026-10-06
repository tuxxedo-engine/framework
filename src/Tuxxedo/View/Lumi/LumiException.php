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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\Exception;

class LumiException extends Exception
{
    public function __construct(
        string $message,
    ) {
        if (static::class !== self::class) {
            $message = \sprintf(
                '%s: %s',
                static::class,
                $message,
            );
        }

        parent::__construct($message);
    }

    public static function fromAmbiguousCompilerAndOptimizers(): self
    {
        return new self(
            message: 'Cannot pass both a compiler and an optimizers list; pass the optimizers to the compiler instead',
        );
    }

    public static function fromAmbiguousCompilerAndIntrospector(): self
    {
        return new self(
            message: 'Cannot pass both a compiler and an introspector; pass the introspector to the compiler instead',
        );
    }

    public static function fromAmbiguousLoaderAndConfigurationHash(): self
    {
        return new self(
            message: 'Cannot pass both a host-supplied loader and a configuration hash; set the configuration hash on the loader itself',
        );
    }

    protected static function formatExpectedGot(
        string $expected,
        string $got,
    ): string {
        return \sprintf(
            'expected %s, got %s',
            self::formatQuoted($expected),
            self::formatQuoted($got),
        );
    }

    protected static function formatLocation(
        int $line,
    ): string {
        return \sprintf(
            ' on line %d',
            $line,
        );
    }

    /**
     * @param string[] $items
     */
    protected static function formatOneOf(
        array $items,
        string $conjunction = 'or',
    ): string {
        $quoted = \array_map(
            self::formatQuoted(...),
            $items,
        );

        $count = \sizeof($quoted);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $quoted[0];
        }

        if ($count === 2) {
            return $quoted[0] . ' ' . $conjunction . ' ' . $quoted[1];
        }

        return \join(', ', $quoted) . ', ' . $conjunction . ' ' . \array_pop($quoted);
    }
}
