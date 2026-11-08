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

namespace Tuxxedo\Serializer\Converter;

use Tuxxedo\Serializer\PropertyContextInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\ValueConverterInterface;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTime;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Temporal\TemporalException;

class TemporalConverter implements ValueConverterInterface
{
    public function __construct(
        public readonly ?string $format = null,
    ) {
    }

    public function decode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        if (!\is_string($value)) {
            throw SerializerException::fromInvalidValue('a date and time string');
        }

        $type = $property->typeName ?? InstantInterface::class;

        try {
            if (\is_a($type, LocalDateInterface::class, true)) {
                if ($this->format === null) {
                    return LocalDate::parse($value);
                }

                $dateTime = $this->parseFormatted($value);

                return LocalDate::of(
                    year: (int) $dateTime->format('Y'),
                    month: (int) $dateTime->format('n'),
                    day: (int) $dateTime->format('j'),
                );
            }

            if (\is_a($type, LocalTimeInterface::class, true)) {
                if ($this->format === null) {
                    return LocalTime::parse($value);
                }

                $dateTime = $this->parseFormatted($value);

                return LocalTime::of(
                    hour: (int) $dateTime->format('G'),
                    minute: (int) $dateTime->format('i'),
                    second: (int) $dateTime->format('s'),
                );
            }

            if ($this->format === null) {
                return Instant::parse($value);
            }

            return Instant::fromDateTime($this->parseFormatted($value));
        } catch (TemporalException) {
            throw SerializerException::fromInvalidValue('a valid date and time');
        }
    }

    public function encode(
        mixed $value,
        PropertyContextInterface $property,
    ): mixed {
        if (
            !$value instanceof InstantInterface &&
            !$value instanceof LocalDateInterface &&
            !$value instanceof LocalTimeInterface
        ) {
            throw SerializerException::fromInvalidValue('a date or time value');
        }

        return $this->format === null
            ? $value->toIso8601()
            : $value->format($this->format);
    }

    /**
     * @throws SerializerException
     */
    private function parseFormatted(
        string $value,
    ): \DateTimeImmutable {
        $dateTime = \DateTimeImmutable::createFromFormat('!' . $this->format, $value);

        if ($dateTime === false) {
            throw SerializerException::fromInvalidValue(
                \sprintf(
                    'a date and time in the format "%s"',
                    $this->format,
                ),
            );
        }

        return $dateTime;
    }
}
