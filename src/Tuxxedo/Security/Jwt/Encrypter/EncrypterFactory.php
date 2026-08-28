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

namespace Tuxxedo\Security\Jwt\Encrypter;

use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;

class EncrypterFactory
{
    /**
     * @throws JwtException
     */
    public static function createFromAlgorithm(
        KeyManagementAlgorithm $algorithm,
        KeyInterface $key,
    ): EncrypterInterface {
        return match ($algorithm) { // @codeCoverageIgnore
            KeyManagementAlgorithm::DIR => self::createDirect(
                key: $key,
            ),
            KeyManagementAlgorithm::A128KW, KeyManagementAlgorithm::A192KW, KeyManagementAlgorithm::A256KW => self::createAesKeyWrap(
                algorithm: $algorithm,
                key: $key,
            ),
        };
    }

    /**
     * @throws JwtException
     */
    public static function createDirect(
        KeyInterface $key,
    ): DirectEncrypter {
        if (!$key instanceof SymmetricKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: KeyManagementAlgorithm::DIR->identifier(),
                expected: SymmetricKey::class,
                given: $key::class,
            );
        }

        return new DirectEncrypter();
    }

    /**
     * @throws JwtException
     */
    public static function createAesKeyWrap(
        KeyManagementAlgorithm $algorithm,
        KeyInterface $key,
    ): AesKeyWrapEncrypter {
        if (!$key instanceof SymmetricKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->identifier(),
                expected: SymmetricKey::class,
                given: $key::class,
            );
        }

        return new AesKeyWrapEncrypter(
            algorithm: $algorithm,
            key: $key,
        );
    }
}
