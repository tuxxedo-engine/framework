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
use Tuxxedo\Security\Jwt\ContentEncryption\AesGcmContentDecrypter;
use Tuxxedo\Security\Jwt\ContentEncryption\AesGcmContentEncrypter;
use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JwtException;

class AesGcmContentEncrypterTest extends TestCase
{
    public function testEncryptDecryptRoundTripForA128Gcm(): void
    {
        $cek = \random_bytes(16);
        $plaintext = 'hello world';
        $aad = 'aad-bytes';

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: $plaintext,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: $aad,
        );

        self::assertSame(
            $plaintext,
            (new AesGcmContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A128GCM,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: $aad,
            ),
        );
    }

    public function testEncryptDecryptRoundTripForA192Gcm(): void
    {
        $cek = \random_bytes(24);

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A192GCM,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            'payload',
            (new AesGcmContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A192GCM,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: '',
            ),
        );
    }

    public function testEncryptDecryptRoundTripForA256Gcm(): void
    {
        $cek = \random_bytes(32);

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A256GCM,
        ))->encrypt(
            plaintext: 'top-secret',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'header',
        );

        self::assertSame(
            'top-secret',
            (new AesGcmContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A256GCM,
            ))->decrypt(
                ciphertext: $result->ciphertext,
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: 'header',
            ),
        );
    }

    public function testEncryptProducesTwelveByteIv(): void
    {
        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \random_bytes(16),
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            12,
            \strlen($result->initializationVector),
        );
    }

    public function testEncryptProducesSixteenByteTag(): void
    {
        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \random_bytes(16),
            additionalAuthenticatedData: '',
        );

        self::assertSame(
            16,
            \strlen($result->authenticationTag),
        );
    }

    public function testEncryptThrowsForWrongCekLength(): void
    {
        $this->expectException(JwtException::class);

        (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'x',
            contentEncryptionKey: \str_repeat("\x00", 24),
            additionalAuthenticatedData: '',
        );
    }

    public function testConstructorThrowsForCbcAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        );
    }
}
