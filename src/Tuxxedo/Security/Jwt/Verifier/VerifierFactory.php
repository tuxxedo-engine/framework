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

namespace Tuxxedo\Security\Jwt\Verifier;

use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class VerifierFactory
{
    /**
     * @throws JwtException
     */
    public static function createFromAlgorithm(
        Algorithm $algorithm,
        KeyInterface $key,
    ): VerifierInterface {
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
    ): HmacVerifier {
        if (!$key instanceof SymmetricKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: SymmetricKey::class,
                given: $key::class,
            );
        }

        return new HmacVerifier(
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
    ): RsaVerifier {
        if (!$key instanceof RsaPublicKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: RsaPublicKey::class,
                given: $key::class,
            );
        }

        return new RsaVerifier(
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
    ): EcdsaVerifier {
        if (!$key instanceof EcdsaPublicKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: $algorithm->name,
                expected: EcdsaPublicKey::class,
                given: $key::class,
            );
        }

        return new EcdsaVerifier(
            algorithm: $algorithm,
            key: $key,
        );
    }

    /**
     * @throws JwtException
     */
    public static function createEdDsa(
        KeyInterface $key,
    ): EdDsaVerifier {
        if (!$key instanceof EdDsaPublicKey) {
            throw JwtException::fromIncompatibleKey(
                algorithm: Algorithm::EDDSA->name,
                expected: EdDsaPublicKey::class,
                given: $key::class,
            );
        }

        return new EdDsaVerifier(
            key: $key,
        );
    }
}
