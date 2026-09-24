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

use Tuxxedo\Model\Attribute\Column\DateFormat;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalDateInterface;

class LocalDateCoercer implements CoercerInterface
{
    private readonly string $formatString;

    public function __construct(
        DateFormat|string $format = DateFormat::DEFAULT,
    ) {
        $this->formatString = $format instanceof DateFormat
            ? $format->value
            : $format;
    }

    public function hydrate(
        string|int|float|bool $value,
    ): LocalDateInterface {
        if (!\is_string($value)) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: 'string',
                actualType: \get_debug_type($value),
            );
        }

        $dateTime = \DateTimeImmutable::createFromFormat($this->formatString, $value);

        if ($dateTime === false) {
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

        return LocalDate::of(
            year: (int) $dateTime->format('Y'),
            month: (int) $dateTime->format('n'),
            day: (int) $dateTime->format('j'),
        );
    }

    public function dehydrate(
        mixed $value,
    ): string {
        if (!$value instanceof LocalDateInterface) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: LocalDateInterface::class,
                actualType: \get_debug_type($value),
            );
        }

        return $value->format($this->formatString);
    }
}
