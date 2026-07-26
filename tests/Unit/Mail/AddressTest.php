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

namespace Unit\Mail;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;

class AddressTest extends TestCase
{
    public function testConstructorAcceptsPlainEmail(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
        );

        self::assertSame('kalle@example.com', $address->email);
        self::assertNull($address->displayName);
        self::assertSame('kalle', $address->localPart);
        self::assertSame('example.com', $address->domain);
    }

    public function testConstructorAcceptsDisplayName(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
            displayName: 'Kalle Sommer',
        );

        self::assertSame('Kalle Sommer', $address->displayName);
    }

    public function testConstructorRejectsEmailWithoutAtSign(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'no-at-sign',
        );
    }

    public function testConstructorRejectsEmailWithMultipleAtSigns(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'a@b@example.com',
        );
    }

    public function testConstructorRejectsEmailWithWhitespace(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'kalle @example.com',
        );
    }

    public function testConstructorRejectsEmailWithEmptyLocalPart(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: '@example.com',
        );
    }

    public function testConstructorRejectsEmailWithEmptyDomain(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'kalle@',
        );
    }

    public function testConstructorRejectsEmailExceedingMaxLength(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: \str_repeat('a', 60) . '@' . \str_repeat('b', 200) . '.com',
        );
    }

    public function testConstructorRejectsLocalPartExceedingMaxLength(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: \str_repeat('a', 65) . '@example.com',
        );
    }

    public function testConstructorRejectsDisplayNameWithNewline(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'kalle@example.com',
            displayName: "Kalle\nInjection",
        );
    }

    public function testConstructorRejectsDisplayNameWithCarriageReturn(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'kalle@example.com',
            displayName: "Kalle\rInjection",
        );
    }

    public function testConstructorRejectsDisplayNameWithNullByte(): void
    {
        $this->expectException(MailException::class);

        new Address(
            email: 'kalle@example.com',
            displayName: "Kalle\x00Injection",
        );
    }

    public function testToRfc5322EmitsBareEmailWithoutDisplayName(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
        );

        self::assertSame(
            'kalle@example.com',
            $address->toRfc5322(),
        );
    }

    public function testToRfc5322QuotesAsciiDisplayName(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
            displayName: 'Kalle Sommer',
        );

        self::assertSame(
            '"Kalle Sommer" <kalle@example.com>',
            $address->toRfc5322(),
        );
    }

    public function testToRfc5322EscapesQuotesAndBackslashesInDisplayName(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
            displayName: 'Kalle "Q" \\Sommer',
        );

        self::assertSame(
            '"Kalle \\"Q\\" \\\\Sommer" <kalle@example.com>',
            $address->toRfc5322(),
        );
    }

    public function testToRfc5322EncodesNonAsciiDisplayNameAsEncodedWord(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
            displayName: 'Käll Sømmer',
        );

        self::assertSame(
            \sprintf(
                '=?UTF-8?B?%s?= <kalle@example.com>',
                \base64_encode('Käll Sømmer'),
            ),
            $address->toRfc5322(),
        );
    }

    public function testIsInternationalizedReturnsFalseForAsciiEmail(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
        );

        self::assertFalse($address->isInternationalized());
    }

    public function testIsInternationalizedReturnsTrueForNonAsciiLocalPart(): void
    {
        $address = new Address(
            email: 'kållê@example.com',
        );

        self::assertTrue($address->isInternationalized());
    }

    public function testIsInternationalizedReturnsTrueForNonAsciiDomain(): void
    {
        $address = new Address(
            email: 'kalle@exämple.com',
        );

        self::assertTrue($address->isInternationalized());
    }

    public function testParseAcceptsBareEmail(): void
    {
        $address = Address::parse(
            raw: 'kalle@example.com',
        );

        self::assertSame('kalle@example.com', $address->email);
        self::assertNull($address->displayName);
    }

    public function testParseAcceptsAngledEmail(): void
    {
        $address = Address::parse(
            raw: '<kalle@example.com>',
        );

        self::assertSame('kalle@example.com', $address->email);
        self::assertNull($address->displayName);
    }

    public function testParseAcceptsUnquotedDisplayName(): void
    {
        $address = Address::parse(
            raw: 'Kalle Sommer <kalle@example.com>',
        );

        self::assertSame('kalle@example.com', $address->email);
        self::assertSame('Kalle Sommer', $address->displayName);
    }

    public function testParseAcceptsQuotedDisplayName(): void
    {
        $address = Address::parse(
            raw: '"Kalle Sommer" <kalle@example.com>',
        );

        self::assertSame('Kalle Sommer', $address->displayName);
    }

    public function testParseUnescapesQuotesAndBackslashesInQuotedDisplayName(): void
    {
        $address = Address::parse(
            raw: '"Kalle \\"Q\\" \\\\Sommer" <kalle@example.com>',
        );

        self::assertSame(
            'Kalle "Q" \\Sommer',
            $address->displayName,
        );
    }

    public function testParseTrimsSurroundingWhitespace(): void
    {
        $address = Address::parse(
            raw: '   kalle@example.com   ',
        );

        self::assertSame('kalle@example.com', $address->email);
    }

    public function testParseRejectsUnparseableInput(): void
    {
        $this->expectException(MailException::class);

        (void) Address::parse(
            raw: 'not an email at all',
        );
    }

    public function testParseRejectsAngledFormWithInvalidEmail(): void
    {
        $this->expectException(MailException::class);

        (void) Address::parse(
            raw: 'Kalle <not-an-email>',
        );
    }
}
