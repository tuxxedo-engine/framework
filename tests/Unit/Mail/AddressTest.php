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

use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testEncodedDomainPassesAsciiDomainThrough(): void
    {
        $address = new Address(
            email: 'kalle@example.com',
        );

        self::assertSame('example.com', $address->encodedDomain);
        self::assertSame('kalle@example.com', $address->toRfc5321());
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function idnaDomainDataProvider(): \Generator
    {
        yield [
            'münchen.example',
            'xn--mnchen-3ya.example',
        ];

        yield [
            'straße.de',
            'xn--strae-oqa.de',
        ];

        yield [
            'Ærø.dk',
            'xn--r-3fa9c.dk',
        ];
    }

    #[DataProvider('idnaDomainDataProvider')]
    public function testEncodedDomainIsIdnaEncodedAndDomainIsKept(
        string $domain,
        string $encodedDomain,
    ): void {
        $address = new Address(
            email: 'kalle@' . $domain,
        );

        self::assertSame($domain, $address->domain);
        self::assertSame($encodedDomain, $address->encodedDomain);
        self::assertSame('kalle@' . $encodedDomain, $address->toRfc5321());
    }

    public function testAddressLiteralDomainIsAccepted(): void
    {
        $address = new Address(
            email: 'kalle@[192.0.2.1]',
        );

        self::assertSame('[192.0.2.1]', $address->encodedDomain);
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function invalidDomainDataProvider(): \Generator
    {
        yield [
            'kalle@example..com',
        ];

        yield [
            'kalle@-example.com',
        ];

        yield [
            'kalle@example-.com',
        ];

        yield [
            'kalle@example.com.',
        ];

        yield [
            'kalle@ex_ample.com',
        ];

        yield [
            'kalle@' . \str_repeat('a', 64) . '.com',
        ];
    }

    #[DataProvider('invalidDomainDataProvider')]
    public function testConstructorRejectsInvalidDomain(
        string $email,
    ): void {
        try {
            new Address(
                email: $email,
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                'is not a valid host name or address literal',
                $exception->getMessage(),
            );
        }
    }

    public function testConstructorRejectsDomainThatFailsIdnaEncoding(): void
    {
        try {
            new Address(
                email: 'kalle@ex_ämple.com',
            );

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString(
                'via IDNA',
                $exception->getMessage(),
            );
        }
    }

    public function testRequiresSmtpUtf8OnlyForNonAsciiLocalPart(): void
    {
        self::assertFalse(
            (new Address(
                email: 'kalle@exämple.com',
            ))->requiresSmtpUtf8(),
        );

        self::assertTrue(
            (new Address(
                email: 'kållê@example.com',
            ))->requiresSmtpUtf8(),
        );
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
