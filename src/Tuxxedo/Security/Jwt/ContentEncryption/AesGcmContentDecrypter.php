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

class AesGcmContentDecrypter implements ContentDecrypterInterface
{
    private readonly string $cipher;
    private readonly int $keyLengthBytes;

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
    }

    public function decrypt(
        string $ciphertext,
        string $initializationVector,
        string $authenticationTag,
        #[\SensitiveParameter] string $contentEncryptionKey,
        string $additionalAuthenticatedData,
    ): string {
        $actual = \strlen($contentEncryptionKey);

        if ($actual !== $this->keyLengthBytes) {
            throw JwtException::fromInvalidSymmetricKeyLength(
                algorithm: $this->cipher,
                expectedBytes: \strval($this->keyLengthBytes),
                actualBytes: $actual,
            );
        }

        $plaintext = \openssl_decrypt(
            data: $ciphertext,
            cipher_algo: $this->cipher,
            passphrase: $contentEncryptionKey,
            options: \OPENSSL_RAW_DATA,
            iv: $initializationVector,
            tag: $authenticationTag,
            aad: $additionalAuthenticatedData,
        );

        if ($plaintext === false) {
            // @codeCoverageIgnoreStart
            throw JwtException::fromContentDecryptionFailed(
                cipher: $this->cipher,
            );
            // @codeCoverageIgnoreEnd
        }

        return $plaintext;
    }
}
