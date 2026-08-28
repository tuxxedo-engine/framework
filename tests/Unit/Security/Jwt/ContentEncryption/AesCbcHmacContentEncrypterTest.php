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

namespace Unit\Security\Jwt\ContentEncryption;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Security\Jwt\ContentEncryption\AesCbcHmacContentDecrypter;
use Tuxxedo\Security\Jwt\ContentEncryption\AesCbcHmacContentEncrypter;
use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JwtException;

class AesCbcHmacContentEncrypterTest extends TestCase
{
    public function testEncryptDecryptRoundTripForA128CbcHs256(): void
    {
        $cek = \random_bytes(32);
        $plaintext = 'hello world';
        $aad = 'aad-bytes';

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: $plaintext,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: $aad,
        );

        self::assertSame(
            $plaintext,
            (new AesCbcHmacContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: $aad,
            ),
        );
    }

    public function testEncryptDecryptRoundTripForA192CbcHs384(): void
    {
        $cek = \random_bytes(48);

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A192CBC_HS384,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            'payload',
            (new AesCbcHmacContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A192CBC_HS384,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: '',
            ),
        );
    }

    public function testEncryptDecryptRoundTripForA256CbcHs512(): void
    {
        $cek = \random_bytes(64);

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A256CBC_HS512,
        ))->encrypt(
            plaintext: 'top-secret',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'header',
        );

        self::assertSame(
            'top-secret',
            (new AesCbcHmacContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A256CBC_HS512,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: 'header',
            ),
        );
    }

    public function testEncryptProducesSixteenByteIv(): void
    {
        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \random_bytes(32),
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            16,
            \strlen($result->initializationVector),
        );
    }

    public function testEncryptTagLengthMatchesVariant(): void
    {
        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A256CBC_HS512,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \random_bytes(64),
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            32,
            \strlen($result->authenticationTag),
        );
    }

    public function testEncryptThrowsForWrongCekLength(): void
    {
        $this->expectException(JwtException::class);

        (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \str_repeat("\x00", 16),
            additionalAuthenticatedData: '',
        );
    }

    public function testConstructorThrowsForGcmAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        );
    }
}
