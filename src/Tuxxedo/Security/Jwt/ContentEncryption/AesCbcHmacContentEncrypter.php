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

class AesCbcHmacContentEncrypter implements ContentEncrypterInterface
{
    private readonly string $cipher;
    private readonly string $hashAlgorithm;
    private readonly int $keyLengthBytes;
    private readonly int $halfKeyLengthBytes;
    private readonly int $ivLengthBytes;
    private readonly int $tagLengthBytes;

    /**
     * @throws JwtException
     */
    public function __construct(
        ContentEncryptionAlgorithm $algorithm,
    ) {
        [$cipher, $hash, $tagLength] = match ($algorithm) {
            ContentEncryptionAlgorithm::A128CBC_HS256 => [
                'aes-128-cbc',
                'sha256',
                16,
            ],
            ContentEncryptionAlgorithm::A192CBC_HS384 => [
                'aes-192-cbc',
                'sha384',
                24,
            ],
            ContentEncryptionAlgorithm::A256CBC_HS512 => [
                'aes-256-cbc',
                'sha512',
                32,
            ],
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->identifier(),
            ),
        };

        $this->cipher = $cipher;
        $this->hashAlgorithm = $hash;
        $this->tagLengthBytes = $tagLength;
        $this->keyLengthBytes = $algorithm->keyLengthBytes();
        $this->halfKeyLengthBytes = \intdiv($this->keyLengthBytes, 2);
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

        $macKey = \substr($contentEncryptionKey, 0, $this->halfKeyLengthBytes);
        $encKey = \substr($contentEncryptionKey, $this->halfKeyLengthBytes);
        $iv = \random_bytes($this->ivLengthBytes);

        $ciphertext = \openssl_encrypt(
            data: $plaintext,
            cipher_algo: $this->cipher,
            passphrase: $encKey,
            options: \OPENSSL_RAW_DATA,
            iv: $iv,
        );

        if ($ciphertext === false) {
            // @codeCoverageIgnoreStart
            throw JwtException::fromEncryptionFailure(
                context: self::class,
            );
            // @codeCoverageIgnoreEnd
        }

        $aadLengthBits = \strlen($additionalAuthenticatedData) * 8;
        $al = \pack('J', $aadLengthBits);
        $mac = \hash_hmac(
            algo: $this->hashAlgorithm,
            data: $additionalAuthenticatedData . $iv . $ciphertext . $al,
            key: $macKey,
            binary: true,
        );

        return new ContentEncryptionResult(
            ciphertext: $ciphertext,
            initializationVector: $iv,
            authenticationTag: \substr($mac, 0, $this->tagLengthBytes),
        );
    }
}
