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

namespace Tuxxedo\Security\Crypto;

class AesKeyWrap
{
    private const string DEFAULT_IV = "\xA6\xA6\xA6\xA6\xA6\xA6\xA6\xA6";

    /**
     * @throws CryptoException
     */
    public static function wrap(
        #[\SensitiveParameter] string $kek,
        #[\SensitiveParameter] string $cek,
    ): string {
        $cekLength = \strlen($cek);

        if ($cekLength < 16 || $cekLength % 8 !== 0) {
            throw CryptoException::fromInvalidCekLength(
                bytes: $cekLength,
            );
        }

        $cipher = self::cipherForKek($kek);
        $blocks = \str_split($cek, 8);
        $n = \sizeof($blocks);
        $a = self::DEFAULT_IV;

        for ($j = 0; $j <= 5; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $encrypted = \openssl_encrypt(
                    data: $a . $blocks[$i],
                    cipher_algo: $cipher,
                    passphrase: $kek,
                    options: \OPENSSL_RAW_DATA | \OPENSSL_ZERO_PADDING,
                    iv: '',
                );

                if ($encrypted === false) {
                    // @codeCoverageIgnoreStart
                    throw CryptoException::fromEncryptionFailure(
                        context: self::class,
                    );
                    // @codeCoverageIgnoreEnd
                }

                $t = ($n * $j) + $i + 1;
                $a = \substr($encrypted, 0, 8) ^ \pack('J', $t);
                $blocks[$i] = \substr($encrypted, 8, 8);
            }
        }

        return $a . \implode('', $blocks);
    }

    /**
     * @throws CryptoException
     */
    public static function unwrap(
        #[\SensitiveParameter] string $kek,
        string $wrappedKey,
    ): string {
        $wrappedLength = \strlen($wrappedKey);

        if ($wrappedLength < 24 || $wrappedLength % 8 !== 0) {
            throw CryptoException::fromInvalidWrappedKeyLength(
                bytes: $wrappedLength,
            );
        }

        $cipher = self::cipherForKek($kek);
        $a = \substr($wrappedKey, 0, 8);
        $blocks = \str_split(\substr($wrappedKey, 8), 8);
        $n = \sizeof($blocks);

        for ($j = 5; $j >= 0; $j--) {
            for ($i = $n - 1; $i >= 0; $i--) {
                $t = ($n * $j) + $i + 1;
                $decrypted = \openssl_decrypt(
                    data: ($a ^ \pack('J', $t)) . $blocks[$i],
                    cipher_algo: $cipher,
                    passphrase: $kek,
                    options: \OPENSSL_RAW_DATA | \OPENSSL_ZERO_PADDING,
                    iv: '',
                );

                if ($decrypted === false) {
                    // @codeCoverageIgnoreStart
                    throw CryptoException::fromEncryptionFailure(
                        context: self::class,
                    );
                    // @codeCoverageIgnoreEnd
                }

                $a = \substr($decrypted, 0, 8);
                $blocks[$i] = \substr($decrypted, 8, 8);
            }
        }

        if (!\hash_equals(self::DEFAULT_IV, $a)) {
            throw CryptoException::fromKeyUnwrapIntegrityFailed();
        }

        return \implode('', $blocks);
    }

    /**
     * @throws CryptoException
     */
    private static function cipherForKek(
        string $kek,
    ): string {
        return match (\strlen($kek)) {
            16 => 'aes-128-ecb',
            24 => 'aes-192-ecb',
            32 => 'aes-256-ecb',
            default => throw CryptoException::fromInvalidSymmetricKeyLength(
                algorithm: 'AES Key Wrap',
                expectedBytes: '16, 24, or 32',
                actualBytes: \strlen($kek),
            ),
        };
    }
}
