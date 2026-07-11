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

namespace Unit\Security\Jwt\Verifier;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Verifier\EcdsaVerifier;
use Tuxxedo\Security\Jwt\Verifier\EdDsaVerifier;
use Tuxxedo\Security\Jwt\Verifier\HmacVerifier;
use Tuxxedo\Security\Jwt\Verifier\RsaVerifier;
use Tuxxedo\Security\Jwt\Verifier\VerifierFactory;

class VerifierFactoryTest extends TestCase
{
    public function testCreateFromAlgorithmDispatchesHmacFamily(): void
    {
        $verifier = VerifierFactory::createFromAlgorithm(
            algorithm: Algorithm::HS512,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        self::assertInstanceOf(
            HmacVerifier::class,
            $verifier,
        );
    }

    public function testCreateFromAlgorithmDispatchesRsaFamily(): void
    {
        $verifier = VerifierFactory::createFromAlgorithm(
            algorithm: Algorithm::RS384,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::assertInstanceOf(
            RsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateFromAlgorithmDispatchesEcdsaFamily(): void
    {
        $verifier = VerifierFactory::createFromAlgorithm(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256PublicPem(),
            ),
        );

        self::assertInstanceOf(
            EcdsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateFromAlgorithmDispatchesEdDsa(): void
    {
        $verifier = VerifierFactory::createFromAlgorithm(
            algorithm: Algorithm::EDDSA,
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );

        self::assertInstanceOf(
            EdDsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateHmacReturnsVerifierForSymmetricKey(): void
    {
        $verifier = VerifierFactory::createHmac(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        self::assertInstanceOf(
            HmacVerifier::class,
            $verifier,
        );
    }

    public function testCreateHmacThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createHmac(
            algorithm: Algorithm::HS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );
    }

    public function testCreateRsaAcceptsRsaPublicKey(): void
    {
        $verifier = VerifierFactory::createRsa(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::assertInstanceOf(
            RsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateRsaThrowsForRsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createRsa(
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );
    }

    public function testCreateRsaThrowsForNonRsaKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createRsa(
            algorithm: Algorithm::RS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );
    }

    public function testCreateEcdsaAcceptsEcdsaPublicKey(): void
    {
        $verifier = VerifierFactory::createEcdsa(
            algorithm: Algorithm::ES384,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP384PublicPem(),
            ),
        );

        self::assertInstanceOf(
            EcdsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateEcdsaThrowsForEcdsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createEcdsa(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );
    }

    public function testCreateEcdsaThrowsForNonEcdsaKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createEcdsa(
            algorithm: Algorithm::ES256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );
    }

    public function testCreateEdDsaAcceptsEdDsaPublicKey(): void
    {
        $verifier = VerifierFactory::createEdDsa(
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );

        self::assertInstanceOf(
            EdDsaVerifier::class,
            $verifier,
        );
    }

    public function testCreateEdDsaThrowsForEdDsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createEdDsa(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );
    }

    public function testCreateEdDsaThrowsForNonEdDsaKey(): void
    {
        $this->expectException(JwtException::class);

        VerifierFactory::createEdDsa(
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );
    }
}
