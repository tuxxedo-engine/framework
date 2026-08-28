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

class AesGcmContentDecrypterTest extends TestCase
{
    public function testDecryptThrowsForWrongCekLength(): void
    {
        $this->expectException(JwtException::class);

        (new AesGcmContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->decrypt(
            ciphertext: 'x',
            initializationVector: \str_repeat("\x00", 12),
            authenticationTag: \str_repeat("\x00", 16),
            contentEncryptionKey: \str_repeat("\x00", 24),
            additionalAuthenticatedData: '',
        );
    }

    public function testDecryptThrowsForTamperedCiphertext(): void
    {
        $cek = \random_bytes(16);

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        $this->expectException(JwtException::class);

        (new AesGcmContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->decrypt(
            ciphertext: $result->ciphertext ^ \str_repeat("\x01", \strlen($result->ciphertext)),
            initializationVector: $result->initializationVector,
            authenticationTag: $result->authenticationTag,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );
    }

    public function testDecryptThrowsForTamperedTag(): void
    {
        $cek = \random_bytes(16);

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        $this->expectException(JwtException::class);

        (new AesGcmContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->decrypt(
            ciphertext: $result->ciphertext,
            initializationVector: $result->initializationVector,
            authenticationTag: $result->authenticationTag ^ \str_repeat("\x01", 16),
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );
    }

    public function testDecryptThrowsForTamperedAad(): void
    {
        $cek = \random_bytes(16);

        $result = (new AesGcmContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'header',
        );

        $this->expectException(JwtException::class);

        (new AesGcmContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128GCM,
        ))->decrypt(
            ciphertext: $result->ciphertext,
            initializationVector: $result->initializationVector,
            authenticationTag: $result->authenticationTag,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'tampered',
        );
    }

    public function testConstructorThrowsForCbcAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new AesGcmContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A256CBC_HS512,
        );
    }
}
