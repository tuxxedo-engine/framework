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

namespace Unit\Uuid;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Uuid\Uuid;
use Tuxxedo\Uuid\UuidException;

class UuidTest extends TestCase
{
    public function testConstructorAcceptsCanonicalForm(): void
    {
        $uuid = new Uuid(
            value: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        );

        self::assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $uuid->value);
    }

    public function testConstructorNormalizesCase(): void
    {
        $uuid = new Uuid(
            value: 'F47AC10B-58CC-4372-A567-0E02B2C3D479',
        );

        self::assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $uuid->value);
    }

    public function testConstructorRejectsMissingHyphens(): void
    {
        $this->expectException(UuidException::class);

        new Uuid(
            value: 'f47ac10b58cc4372a5670e02b2c3d479',
        );
    }

    public function testConstructorRejectsNonHexCharacter(): void
    {
        $this->expectException(UuidException::class);

        new Uuid(
            value: 'g47ac10b-58cc-4372-a567-0e02b2c3d479',
        );
    }

    public function testFromBytesRejectsWrongLength(): void
    {
        $this->expectException(UuidException::class);

        Uuid::fromBytes(
            bytes: 'short',
        );
    }

    public function testFromBytesFormatsCanonical(): void
    {
        $bytes = \hex2bin('f47ac10b58cc4372a5670e02b2c3d479');
        self::assertNotFalse($bytes);

        $uuid = Uuid::fromBytes(
            bytes: $bytes,
        );

        self::assertSame('f47ac10b-58cc-4372-a567-0e02b2c3d479', $uuid->value);
    }

    public function testGetVersionReadsVersionNibble(): void
    {
        $uuid = new Uuid(
            value: 'f47ac10b-58cc-7372-a567-0e02b2c3d479',
        );

        self::assertSame(7, $uuid->getVersion());
    }

    public function testEqualsComparesValues(): void
    {
        $a = new Uuid(
            value: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        );
        $b = new Uuid(
            value: 'F47AC10B-58CC-4372-A567-0E02B2C3D479',
        );
        $c = new Uuid(
            value: '00000000-0000-4000-8000-000000000000',
        );

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
