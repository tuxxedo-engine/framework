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

namespace Tuxxedo\Security\Jwt\Signer;

use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class SignerFactory
{
    /**
     * @throws JwtException
     */
    public static function createFromAlgorithm(
        Algorithm $algorithm,
        KeyInterface $key,
    ): SignerInterface {
        return match ($algorithm) {
            Algorithm::HS256, Algorithm::HS384, Algorithm::HS512 => self::createHmac(
                algorithm: $algorithm,
                key: $key,
            ),
            Algorithm::RS256, Algorithm::RS384, Algorithm::RS512 => self::createRsa(
                algorithm: $algorithm,
                key: $key,
            ),
            Algorithm::ES256, Algorithm::ES384, Algorithm::ES512 => self::createEcdsa(
                algorithm: $algorithm,
                key: $key,
            ),
            Algorithm::EDDSA => self::createEdDsa(
                key: $key,
            ),
        };
    }

    /**
     * @throws JwtException
     */
    public static function createHmac(
        Algorithm $algorithm,
        KeyInterface $key,
    ): HmacSigner {
        if (!$key instanceof SymmetricKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: SymmetricKey::class,
                given: $key::class,
            );
        }

        return new HmacSigner(
            algorithm: $algorithm,
            key: $key,
        );
    }

    /**
     * @throws JwtException
     */
    public static function createRsa(
        Algorithm $algorithm,
        KeyInterface $key,
    ): RsaSigner {
        if (!$key instanceof RsaPrivateKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: RsaPrivateKey::class,
                given: $key::class,
            );
        }

        return new RsaSigner(
            algorithm: $algorithm,
            key: $key,
        );
    }

    /**
     * @throws JwtException
     */
    public static function createEcdsa(
        Algorithm $algorithm,
        KeyInterface $key,
    ): EcdsaSigner {
        if (!$key instanceof EcdsaPrivateKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: EcdsaPrivateKey::class,
                given: $key::class,
            );
        }

        return new EcdsaSigner(
            algorithm: $algorithm,
            key: $key,
        );
    }

    /**
     * @throws JwtException
     */
    public static function createEdDsa(
        KeyInterface $key,
    ): EdDsaSigner {
        if (!$key instanceof EdDsaPrivateKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: Algorithm::EDDSA->name,
                expected: EdDsaPrivateKey::class,
                given: $key::class,
            );
        }

        return new EdDsaSigner(
            key: $key,
        );
    }
}
