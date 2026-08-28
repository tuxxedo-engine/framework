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

namespace Tuxxedo\Security\Jwt\Decrypter;

use Tuxxedo\Security\Crypto\AesKeyWrap;
use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;

class AesKeyWrapDecrypter implements DecrypterInterface
{
    /**
     * @throws JwtException
     */
    public function __construct(
        KeyManagementAlgorithm $algorithm,
        private readonly SymmetricKey $key,
    ) {
        $expected = match ($algorithm) {
            KeyManagementAlgorithm::A128KW => 16,
            KeyManagementAlgorithm::A192KW => 24,
            KeyManagementAlgorithm::A256KW => 32,
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->identifier(),
            ),
        };

        $actual = \strlen($this->key->secret);

        if ($actual !== $expected) {
            throw JwtException::fromInvalidSymmetricKeyLength(
                algorithm: $algorithm->identifier(),
                expectedBytes: \strval($expected),
                actualBytes: $actual,
            );
        }
    }

    /**
     * @throws JwtException
     */
    public function unwrapKey(
        string $wrappedKey,
    ): string {
        try {
            return AesKeyWrap::unwrap(
                kek: $this->key->secret,
                wrappedKey: $wrappedKey,
            );
        } catch (CryptoException $e) {
            throw JwtException::fromCryptoFailure(
                previous: $e,
            );
        }
    }
}
