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

namespace Unit\Security\Crypto\Der;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Crypto\Der\DerEncoder;

class DerEncoderTest extends TestCase
{
    public function testLengthEncoderShortFormZero(): void
    {
        self::assertSame(
            "\x00",
            DerEncoder::length(
                length: 0,
            ),
        );
    }

    public function testLengthEncoderShortFormOne(): void
    {
        self::assertSame(
            "\x01",
            DerEncoder::length(
                length: 1,
            ),
        );
    }

    public function testLengthEncoderShortFormMax(): void
    {
        self::assertSame(
            "\x7F",
            DerEncoder::length(
                length: 127,
            ),
        );
    }

    public function testLengthEncoderLongFormOneByte(): void
    {
        self::assertSame(
            "\x81\x80",
            DerEncoder::length(
                length: 128,
            ),
        );

        self::assertSame(
            "\x81\xFF",
            DerEncoder::length(
                length: 255,
            ),
        );
    }

    public function testLengthEncoderLongFormTwoBytes(): void
    {
        self::assertSame(
            "\x82\x01\x00",
            DerEncoder::length(
                length: 256,
            ),
        );

        self::assertSame(
            "\x82\xFF\xFF",
            DerEncoder::length(
                length: 65535,
            ),
        );
    }

    public function testLengthEncoderLongFormThreeBytes(): void
    {
        self::assertSame(
            "\x83\x01\x00\x00",
            DerEncoder::length(
                length: 65536,
            ),
        );

        self::assertSame(
            "\x83\xFF\xFF\xFF",
            DerEncoder::length(
                length: 16777215,
            ),
        );
    }

    public function testLengthEncoderLongFormFourBytes(): void
    {
        self::assertSame(
            "\x84\x01\x00\x00\x00",
            DerEncoder::length(
                length: 16777216,
            ),
        );

        self::assertSame(
            "\x84\xFF\xFF\xFF\xFF",
            DerEncoder::length(
                length: 0xFFFFFFFF,
            ),
        );
    }

    public function testLengthEncoderThrowsOnOverflow(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::length(
            length: 0xFFFFFFFF + 1,
        );
    }

    public function testIntegerEncoderSingleByteNoHighBit(): void
    {
        self::assertSame(
            "\x02\x01\x2A",
            DerEncoder::integer(
                bytes: "\x2A",
            ),
        );
    }

    public function testIntegerEncoderPrependsZeroWhenHighBitSet(): void
    {
        self::assertSame(
            "\x02\x02\x00\x80",
            DerEncoder::integer(
                bytes: "\x80",
            ),
        );

        self::assertSame(
            "\x02\x02\x00\xFF",
            DerEncoder::integer(
                bytes: "\xFF",
            ),
        );
    }

    public function testIntegerEncoderStripsLeadingZeros(): void
    {
        self::assertSame(
            "\x02\x02\x01\x02",
            DerEncoder::integer(
                bytes: "\x00\x01\x02",
            ),
        );
    }

    public function testIntegerEncoderStripsLeadingZerosAndPrependsForHighBit(): void
    {
        self::assertSame(
            "\x02\x02\x00\xFF",
            DerEncoder::integer(
                bytes: "\x00\x00\xFF",
            ),
        );
    }

    public function testIntegerEncoderZeroInputYieldsSingleZeroByte(): void
    {
        self::assertSame(
            "\x02\x01\x00",
            DerEncoder::integer(
                bytes: "\x00",
            ),
        );
    }

    public function testIntegerEncoderEmptyInputYieldsSingleZeroByte(): void
    {
        self::assertSame(
            "\x02\x01\x00",
            DerEncoder::integer(
                bytes: '',
            ),
        );
    }

    public function testIntegerEncoderMultipleAllZerosCollapse(): void
    {
        self::assertSame(
            "\x02\x01\x00",
            DerEncoder::integer(
                bytes: "\x00\x00\x00\x00",
            ),
        );
    }

    public function testBitStringEmpty(): void
    {
        self::assertSame(
            "\x03\x01\x00",
            DerEncoder::bitString(
                bytes: '',
            ),
        );
    }

    public function testBitStringSingleByte(): void
    {
        self::assertSame(
            "\x03\x02\x00\xFF",
            DerEncoder::bitString(
                bytes: "\xFF",
            ),
        );
    }

    public function testBitStringMultipleBytes(): void
    {
        self::assertSame(
            "\x03\x04\x00\x01\x02\x03",
            DerEncoder::bitString(
                bytes: "\x01\x02\x03",
            ),
        );
    }

    public function testOctetStringWrapsBytes(): void
    {
        self::assertSame(
            "\x04\x03\x01\x02\x03",
            DerEncoder::octetString(
                bytes: "\x01\x02\x03",
            ),
        );
    }

    public function testNullEmitsCanonicalTwoBytes(): void
    {
        self::assertSame(
            "\x05\x00",
            DerEncoder::null(),
        );
    }

    public function testSequenceEmpty(): void
    {
        self::assertSame(
            "\x30\x00",
            DerEncoder::sequence(),
        );
    }

    public function testSequenceSingleChild(): void
    {
        self::assertSame(
            "\x30\x03\x02\x01\x01",
            DerEncoder::sequence(
                DerEncoder::integer(
                    bytes: "\x01",
                ),
            ),
        );
    }

    public function testSequenceMultipleChildren(): void
    {
        self::assertSame(
            "\x30\x06\x02\x01\x01\x02\x01\x02",
            DerEncoder::sequence(
                DerEncoder::integer(
                    bytes: "\x01",
                ),
                DerEncoder::integer(
                    bytes: "\x02",
                ),
            ),
        );
    }

    public function testContextExplicitTagZero(): void
    {
        self::assertSame(
            "\xA0\x03\x02\x01\x01",
            DerEncoder::contextExplicit(
                tag: 0,
                inner: "\x02\x01\x01",
            ),
        );
    }

    public function testContextExplicitTagOne(): void
    {
        self::assertSame(
            "\xA1\x03\x03\x01\x00",
            DerEncoder::contextExplicit(
                tag: 1,
                inner: "\x03\x01\x00",
            ),
        );
    }

    public function testContextExplicitThrowsForNegativeTag(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::contextExplicit(
            tag: -1,
            inner: '',
        );
    }

    public function testContextExplicitThrowsForTagAboveThirty(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::contextExplicit(
            tag: 31,
            inner: '',
        );
    }

    public function testObjectIdentifierRsaEncryption(): void
    {
        self::assertSame(
            "\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01",
            DerEncoder::objectIdentifier(
                oid: '1.2.840.113549.1.1.1',
            ),
        );
    }

    public function testObjectIdentifierIdEcPublicKey(): void
    {
        self::assertSame(
            "\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01",
            DerEncoder::objectIdentifier(
                oid: '1.2.840.10045.2.1',
            ),
        );
    }

    public function testObjectIdentifierP256(): void
    {
        self::assertSame(
            "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07",
            DerEncoder::objectIdentifier(
                oid: '1.2.840.10045.3.1.7',
            ),
        );
    }

    public function testObjectIdentifierP384(): void
    {
        self::assertSame(
            "\x06\x05\x2B\x81\x04\x00\x22",
            DerEncoder::objectIdentifier(
                oid: '1.3.132.0.34',
            ),
        );
    }

    public function testObjectIdentifierP521(): void
    {
        self::assertSame(
            "\x06\x05\x2B\x81\x04\x00\x23",
            DerEncoder::objectIdentifier(
                oid: '1.3.132.0.35',
            ),
        );
    }

    public function testObjectIdentifierThrowsForEmptyString(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::objectIdentifier(
            oid: '',
        );
    }

    public function testObjectIdentifierThrowsForSingleComponent(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::objectIdentifier(
            oid: '1',
        );
    }

    public function testObjectIdentifierThrowsForNonNumericComponent(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::objectIdentifier(
            oid: '1.abc.2',
        );
    }

    public function testObjectIdentifierThrowsForFirstComponentOutOfRange(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::objectIdentifier(
            oid: '3.1.2',
        );
    }

    public function testObjectIdentifierThrowsForSecondComponentOutOfRange(): void
    {
        $this->expectException(CryptoException::class);

        DerEncoder::objectIdentifier(
            oid: '1.40.1',
        );
    }
}
