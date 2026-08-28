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

namespace Tuxxedo\Security\Jwt\ContentEncryption;

use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JwtException;

class AesGcmContentEncrypter implements ContentEncrypterInterface
{
    private readonly string $cipher;
    private readonly int $keyLengthBytes;
    private readonly int $ivLengthBytes;

    /**
     * @throws JwtException
     */
    public function __construct(
        ContentEncryptionAlgorithm $algorithm,
    ) {
        $this->cipher = match ($algorithm) {
            ContentEncryptionAlgorithm::A128GCM => 'aes-128-gcm',
            ContentEncryptionAlgorithm::A192GCM => 'aes-192-gcm',
            ContentEncryptionAlgorithm::A256GCM => 'aes-256-gcm',
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->identifier(),
            ),
        };

        $this->keyLengthBytes = $algorithm->keyLengthBytes();
        $this->ivLengthBytes = $algorithm->ivLengthBytes();
    }

    public function encrypt(
        #[\SensitiveParameter] string $plaintext,
        #[\SensitiveParameter] string $contentEncryptionKey,
        string $additionalAuthenticatedData,
    ): ContentEncryptionResult {
        $actual = \strlen($contentEncryptionKey);

        if ($actual !== $this->keyLengthBytes) {
            throw JwtException::fromInvalidSymmetricKeyLength(
                algorithm: $this->cipher,
                expectedBytes: \strval($this->keyLengthBytes),
                actualBytes: $actual,
            );
        }

        $iv = \random_bytes($this->ivLengthBytes);
        $tag = '';

        $ciphertext = \openssl_encrypt(
            data: $plaintext,
            cipher_algo: $this->cipher,
            passphrase: $contentEncryptionKey,
            options: \OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
            aad: $additionalAuthenticatedData,
            tag_length: 16,
        );

        if ($ciphertext === false) {
            // @codeCoverageIgnoreStart
            throw JwtException::fromEncryptionFailure(
                context: self::class,
            );
            // @codeCoverageIgnoreEnd
        }

        return new ContentEncryptionResult(
            ciphertext: $ciphertext,
            initializationVector: $iv,
            authenticationTag: $tag,
        );
    }
}
