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

use Tuxxedo\Model\Attribute\Column\TimeFormat;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Temporal\LocalTimeInterface;

class LocalTimeCoercer implements CoercerInterface
{
    private const int NANOS_PER_MICROSECOND = 1_000;

    private readonly string $formatString;

    public function __construct(
        TimeFormat|string $format = TimeFormat::DEFAULT,
    ) {
        $this->formatString = $format instanceof TimeFormat
            ? $format->value
            : $format;
    }

    public function hydrate(
        string|int|float|bool $value,
    ): LocalTimeInterface {
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

        return LocalTime::of(
            hour: (int) $dateTime->format('G'),
            minute: (int) $dateTime->format('i'),
            second: (int) $dateTime->format('s'),
            nanosecond: ((int) $dateTime->format('u')) * self::NANOS_PER_MICROSECOND,
        );
    }

    public function dehydrate(
        mixed $value,
    ): string {
        if (!$value instanceof LocalTimeInterface) {
            throw ModelException::fromCoercionFailure(
                coercerClass: static::class,
                expectedType: LocalTimeInterface::class,
                actualType: \get_debug_type($value),
            );
        }

        return $value->format($this->formatString);
    }
}
