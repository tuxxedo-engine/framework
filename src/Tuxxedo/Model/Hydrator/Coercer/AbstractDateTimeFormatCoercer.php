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

namespace Tuxxedo\Model\Hydrator\Coercer;

use Tuxxedo\Model\ModelException;

abstract class AbstractDateTimeFormatCoercer implements CoercerInterface
{
    public function __construct(
        private readonly string $formatString,
    ) {
    }

    public function hydrate(
        string|int|float|bool $value,
    ): \DateTimeImmutable {
        if (!\is_string($value)) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: 'string',
                actualType: \get_debug_type($value),
            );
        }

        $result = \DateTimeImmutable::createFromFormat($this->formatString, $value);

        if ($result === false) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: \sprintf(
                    'string matching format "%s"',
                    $this->formatString,
                ),
                actualType: \sprintf(
                    'string "%s"',
                    $value,
                ),
            );
        }

        return $result;
    }

    public function dehydrate(
        mixed $value,
    ): string|int|float|bool {
        if (!$value instanceof \DateTimeInterface) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: \DateTimeInterface::class,
                actualType: \get_debug_type($value),
            );
        }

        return $value->format($this->formatString);
    }
}
