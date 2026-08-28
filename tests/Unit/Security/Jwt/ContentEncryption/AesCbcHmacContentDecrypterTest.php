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

class AesCbcHmacContentDecrypterTest extends TestCase
{
    public function testDecryptThrowsForWrongCekLength(): void
    {
        $this->expectException(JwtException::class);

        (new AesCbcHmacContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->decrypt(
            ciphertext: 'x',
            initializationVector: \str_repeat("\x00", 16),
            authenticationTag: \str_repeat("\x00", 16),
            contentEncryptionKey: \str_repeat("\x00", 16),
            additionalAuthenticatedData: '',
        );
    }

    public function testDecryptThrowsForTamperedCiphertext(): void
    {
        $cek = \random_bytes(32);

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        try {
            (new AesCbcHmacContentDecrypter(
                algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
            ))->decrypt(
                ciphertext: $result->ciphertext ^ \str_repeat("\x01", \strlen($result->ciphertext)),
                initializationVector: $result->initializationVector,
                authenticationTag: $result->authenticationTag,
                contentEncryptionKey: $cek,
                additionalAuthenticatedData: '',
            );

            self::fail('Expected JwtException');
        } catch (JwtException $exception) {
            self::assertStringContainsString(
                'Authentication tag mismatch',
                $exception->getMessage(),
            );
        }
    }

    public function testDecryptThrowsForTamperedTag(): void
    {
        $cek = \random_bytes(32);

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: '',
        );

        $this->expectException(JwtException::class);

        (new AesCbcHmacContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
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
        $cek = \random_bytes(32);

        $result = (new AesCbcHmacContentEncrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->encrypt(
            plaintext: 'payload',
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'header',
        );

        $this->expectException(JwtException::class);

        (new AesCbcHmacContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A128CBC_HS256,
        ))->decrypt(
            ciphertext: $result->ciphertext,
            initializationVector: $result->initializationVector,
            authenticationTag: $result->authenticationTag,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: 'tampered',
        );
    }

    public function testConstructorThrowsForGcmAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new AesCbcHmacContentDecrypter(
            algorithm: ContentEncryptionAlgorithm::A256GCM,
        );
    }
}
