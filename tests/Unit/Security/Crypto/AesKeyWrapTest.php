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
use Tuxxedo\Security\Crypto\AesKeyWrap;
use Tuxxedo\Security\Crypto\CryptoException;

class AesKeyWrapTest extends TestCase
{
    private static function bytes(
        string $hex,
    ): string {
        /** @var string */
        return \hex2bin($hex);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function providesRfc3394Vectors(): array
    {
        return [
            'section 4.1: 128-bit KEK, 128-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F',
                '00112233445566778899AABBCCDDEEFF',
                '1FA68B0A8112B447AEF34BD8FB5A7B829D3E862371D2CFE5',
            ],
            'section 4.2: 192-bit KEK, 128-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F1011121314151617',
                '00112233445566778899AABBCCDDEEFF',
                '96778B25AE6CA435F92B5B97C050AED2468AB8A17AD84E5D',
            ],
            'section 4.3: 256-bit KEK, 128-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F101112131415161718191A1B1C1D1E1F',
                '00112233445566778899AABBCCDDEEFF',
                '64E8C3F9CE0F5BA263E9777905818A2A93C8191E7D6E8AE7',
            ],
            'section 4.4: 192-bit KEK, 192-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F1011121314151617',
                '00112233445566778899AABBCCDDEEFF0001020304050607',
                '031D33264E15D33268F24EC260743EDCE1C6C7DDEE725A936BA814915C6762D2',
            ],
            'section 4.5: 256-bit KEK, 192-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F101112131415161718191A1B1C1D1E1F',
                '00112233445566778899AABBCCDDEEFF0001020304050607',
                'A8F9BC1612C68B3FF6E6F4FBE30E71E4769C8B80A32CB8958CD5D17D6B254DA1',
            ],
            'section 4.6: 256-bit KEK, 256-bit CEK' => [
                '000102030405060708090A0B0C0D0E0F101112131415161718191A1B1C1D1E1F',
                '00112233445566778899AABBCCDDEEFF000102030405060708090A0B0C0D0E0F',
                '28C9F404C4B810F4CBCCB35CFB87F8263F5786E2D80ED326CBC7F0E71A99F43BFB988B9B7A02DD21',
            ],
        ];
    }

    #[DataProvider('providesRfc3394Vectors')]
    public function testWrapMatchesRfc3394Vector(
        string $kekHex,
        string $cekHex,
        string $wrappedHex,
    ): void {
        self::assertSame(
            self::bytes($wrappedHex),
            AesKeyWrap::wrap(
                kek: self::bytes($kekHex),
                cek: self::bytes($cekHex),
            ),
        );
    }

    #[DataProvider('providesRfc3394Vectors')]
    public function testUnwrapReversesRfc3394Vector(
        string $kekHex,
        string $cekHex,
        string $wrappedHex,
    ): void {
        self::assertSame(
            self::bytes($cekHex),
            AesKeyWrap::unwrap(
                kek: self::bytes($kekHex),
                wrappedKey: self::bytes($wrappedHex),
            ),
        );
    }

    public function testWrapThrowsWhenCekIsShorterThanSixteenBytes(): void
    {
        $this->expectException(CryptoException::class);

        AesKeyWrap::wrap(
            kek: \str_repeat("\x00", 16),
            cek: \str_repeat("\x00", 8),
        );
    }

    public function testWrapThrowsWhenCekIsNotMultipleOfEight(): void
    {
        $this->expectException(CryptoException::class);

        AesKeyWrap::wrap(
            kek: \str_repeat("\x00", 16),
            cek: \str_repeat("\x00", 17),
        );
    }

    public function testUnwrapThrowsWhenWrappedKeyIsShorterThanTwentyFour(): void
    {
        $this->expectException(CryptoException::class);

        AesKeyWrap::unwrap(
            kek: \str_repeat("\x00", 16),
            wrappedKey: \str_repeat("\x00", 16),
        );
    }

    public function testUnwrapThrowsWhenWrappedKeyIsNotMultipleOfEight(): void
    {
        $this->expectException(CryptoException::class);

        AesKeyWrap::unwrap(
            kek: \str_repeat("\x00", 16),
            wrappedKey: \str_repeat("\x00", 25),
        );
    }

    public function testUnwrapThrowsWhenIntegrityCheckFails(): void
    {
        try {
            AesKeyWrap::unwrap(
                kek: self::bytes('000102030405060708090A0B0C0D0E0F'),
                wrappedKey: self::bytes('1FA68B0A8112B447AEF34BD8FB5A7B829D3E862371D2CFE6'),
            );

            self::fail('Expected CryptoException');
        } catch (CryptoException $exception) {
            self::assertStringContainsString(
                'integrity check failed',
                $exception->getMessage(),
            );
        }
    }

    public function testWrapThrowsForKekOfInvalidLength(): void
    {
        $this->expectException(CryptoException::class);

        AesKeyWrap::wrap(
            kek: \str_repeat("\x00", 20),
            cek: \str_repeat("\x00", 16),
        );
    }
}
