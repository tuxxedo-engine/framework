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

namespace Unit\Model\Attribute\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Dialect\SqliteDialect;
use Tuxxedo\Model\Attribute\Column\Date;
use Tuxxedo\Model\Attribute\Column\DateFormat;
use Tuxxedo\Model\Attribute\Column\DateTime;
use Tuxxedo\Model\Attribute\Column\Time;
use Tuxxedo\Model\Attribute\Column\TimeFormat;
use Tuxxedo\Model\Attribute\Column\Timestamp;
use Tuxxedo\Model\Attribute\ColumnFormatInterface;

class ColumnFormatTest extends TestCase
{
    /**
     * @return \Generator<int, array{0: ColumnFormatInterface, 1: string}>
     */
    public static function enumFormatProvider(): \Generator
    {
        yield [
            new Date(),
            'Y-m-d',
        ];

        yield [
            new DateTime(),
            'Y-m-d',
        ];

        yield [
            new Time(),
            'H:i:s',
        ];

        yield [
            new Timestamp(),
            'Y-m-d',
        ];

        yield [
            new Date(format: DateFormat::EUROPEAN),
            'd/m/Y',
        ];

        yield [
            new Time(format: TimeFormat::TWELVE),
            'h:i:s A',
        ];
    }

    #[DataProvider('enumFormatProvider')]
    public function testGetFormatReturnsUnderlyingStringWhenConstructedWithFormatEnum(
        ColumnFormatInterface $column,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $column->getFormat(new SqliteDialect()),
        );
    }

    /**
     * @return \Generator<int, array{0: ColumnFormatInterface, 1: string}>
     */
    public static function rawStringFormatProvider(): \Generator
    {
        yield [
            new Date(format: 'j M Y'),
            'j M Y',
        ];

        yield [
            new DateTime(format: 'Y-m-d\TH:i:sP'),
            'Y-m-d\TH:i:sP',
        ];

        yield [
            new Time(format: 'g:i A'),
            'g:i A',
        ];

        yield [
            new Timestamp(format: 'D, d M Y H:i:s O'),
            'D, d M Y H:i:s O',
        ];
    }

    #[DataProvider('rawStringFormatProvider')]
    public function testGetFormatReturnsRawStringUnchangedWhenConstructedWithFormatString(
        ColumnFormatInterface $column,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $column->getFormat(new SqliteDialect()),
        );
    }
}
