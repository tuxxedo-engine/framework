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

namespace Unit\Security\Crypto;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Security\Crypto\Base64Url;
use Tuxxedo\Security\Crypto\CryptoException;

class Base64UrlTest extends TestCase
{
    public function testEncodesEmptyStringToEmptyString(): void
    {
        self::assertSame(
            '',
            Base64Url::encode(
                bytes: '',
            ),
        );
    }

    public function testEncodeStripsPaddingEquals(): void
    {
        self::assertSame(
            'Zg',
            Base64Url::encode(
                bytes: 'f',
            ),
        );

        self::assertSame(
            'Zm8',
            Base64Url::encode(
                bytes: 'fo',
            ),
        );

        self::assertSame(
            'Zm9v',
            Base64Url::encode(
                bytes: 'foo',
            ),
        );
    }

    public function testEncodeReplacesPlusAndSlashWithUrlSafeCharacters(): void
    {
        $bytes = "\xFB\xFF\xBF";

        $standard = \base64_encode($bytes);

        self::assertStringContainsString(
            '+',
            $standard,
        );

        self::assertStringContainsString(
            '/',
            $standard,
        );

        $urlSafe = Base64Url::encode(
            bytes: $bytes,
        );

        self::assertStringNotContainsString(
            '+',
            $urlSafe,
        );

        self::assertStringNotContainsString(
            '/',
            $urlSafe,
        );

        self::assertStringNotContainsString(
            '=',
            $urlSafe,
        );
    }

    /**
     * @return \Generator<int, array{0: string}>
     */
    public static function roundTripProvider(): \Generator
    {
        yield [
            '',
        ];

        yield [
            'a',
        ];

        yield [
            'foo bar baz',
        ];

        yield [
            "\x00\x01\x02\xFB\xFF\xBF",
        ];

        yield [
            \str_repeat("\xFF", 32),
        ];

        yield [
            'The quick brown fox jumps over the lazy dog',
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testDecodeReversesEncode(
        string $bytes,
    ): void {
        self::assertSame(
            $bytes,
            Base64Url::decode(
                segment: Base64Url::encode(
                    bytes: $bytes,
                ),
            ),
        );
    }

    public function testDecodeAcceptsUnpaddedInput(): void
    {
        self::assertSame(
            'f',
            Base64Url::decode(
                segment: 'Zg',
            ),
        );

        self::assertSame(
            'fo',
            Base64Url::decode(
                segment: 'Zm8',
            ),
        );
    }

    public function testDecodeThrowsForInvalidCharacters(): void
    {
        $this->expectException(CryptoException::class);

        Base64Url::decode(
            segment: '!!!not-base64!!!',
        );
    }

    public function testDecodeThrowsForNonBase64Punctuation(): void
    {
        $this->expectException(CryptoException::class);

        Base64Url::decode(
            segment: '@@@',
        );
    }
}
