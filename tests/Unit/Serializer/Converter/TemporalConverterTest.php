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

namespace Unit\Serializer\Converter;

use Fixture\Serializer\Schedule;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Serializer\Casting;
use Tuxxedo\Serializer\Converter\TemporalConverter;
use Tuxxedo\Serializer\PropertyContext;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Temporal\Instant;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDate;
use Tuxxedo\Temporal\LocalTime;

class TemporalConverterTest extends TestCase
{
    public function testDecodeDefaultsToInstantWithoutType(): void
    {
        $value = new TemporalConverter()->decode('2026-11-08T10:00:00+00:00', self::context(null));

        self::assertInstanceOf(InstantInterface::class, $value);
    }

    public function testDecodeByType(): void
    {
        $converter = new TemporalConverter();

        self::assertInstanceOf(Instant::class, $converter->decode('2026-11-08T10:00:00+00:00', self::context(Instant::class)));
        self::assertInstanceOf(LocalDate::class, $converter->decode('2026-11-08', self::context(LocalDate::class)));
        self::assertInstanceOf(LocalTime::class, $converter->decode('10:00:00', self::context(LocalTime::class)));
    }

    public function testDecodeWithFormat(): void
    {
        $date = new TemporalConverter('d/m/Y')->decode('08/11/2026', self::context(LocalDate::class));

        self::assertInstanceOf(LocalDate::class, $date);
        self::assertSame('2026-11-08', $date->toIso8601());
    }

    public function testDecodeRejectsNonStrings(): void
    {
        try {
            new TemporalConverter()->decode(20261108, self::context(LocalDate::class));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('date and time string', $exception->getMessage());
        }
    }

    public function testDecodeRejectsValuesNotMatchingTheFormat(): void
    {
        try {
            new TemporalConverter('d/m/Y')->decode('2026-11-08', self::context(LocalDate::class));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('"d/m/Y"', $exception->getMessage());
        }
    }

    public function testEncode(): void
    {
        $date = LocalDate::of(2026, 11, 8);

        self::assertSame('2026-11-08', new TemporalConverter()->encode($date, self::context(LocalDate::class)));
        self::assertSame('08/11/2026', new TemporalConverter('d/m/Y')->encode($date, self::context(LocalDate::class)));
    }

    public function testEncodeRejectsNonTemporalValues(): void
    {
        try {
            new TemporalConverter()->encode('2026-11-08', self::context(LocalDate::class));

            self::fail('Expected SerializerException was not thrown');
        } catch (SerializerException $exception) {
            self::assertStringContainsString('date or time value', $exception->getMessage());
        }
    }

    private static function context(
        ?string $typeName,
    ): PropertyContext {
        return new PropertyContext(
            className: Schedule::class,
            propertyName: 'day',
            path: 'day',
            typeName: $typeName,
            nullable: false,
            casting: Casting::STRICT,
        );
    }
}
