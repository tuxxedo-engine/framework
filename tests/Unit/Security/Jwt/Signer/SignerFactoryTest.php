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

namespace Unit\Security\Jwt\Signer;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Signer\EcdsaSigner;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;
use Tuxxedo\Security\Jwt\Signer\HmacSigner;
use Tuxxedo\Security\Jwt\Signer\RsaSigner;
use Tuxxedo\Security\Jwt\Signer\SignerFactory;

class SignerFactoryTest extends TestCase
{
    public function testCreateFromAlgorithmDispatchesHmacFamily(): void
    {
        $signer = SignerFactory::createFromAlgorithm(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        self::assertInstanceOf(
            HmacSigner::class,
            $signer,
        );
    }

    public function testCreateFromAlgorithmDispatchesRsaFamily(): void
    {
        $signer = SignerFactory::createFromAlgorithm(
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );

        self::assertInstanceOf(
            RsaSigner::class,
            $signer,
        );
    }

    public function testCreateFromAlgorithmDispatchesEcdsaFamily(): void
    {
        $signer = SignerFactory::createFromAlgorithm(
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        self::assertInstanceOf(
            EcdsaSigner::class,
            $signer,
        );
    }

    public function testCreateFromAlgorithmDispatchesEdDsa(): void
    {
        $signer = SignerFactory::createFromAlgorithm(
            algorithm: Algorithm::EDDSA,
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        self::assertInstanceOf(
            EdDsaSigner::class,
            $signer,
        );
    }

    public function testCreateHmacReturnsSignerForSymmetricKey(): void
    {
        $signer = SignerFactory::createHmac(
            algorithm: Algorithm::HS384,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        self::assertInstanceOf(
            HmacSigner::class,
            $signer,
        );
    }

    public function testCreateHmacThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        SignerFactory::createHmac(
            algorithm: Algorithm::HS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );
    }

    public function testCreateRsaReturnsSignerForRsaPrivateKey(): void
    {
        $signer = SignerFactory::createRsa(
            algorithm: Algorithm::RS512,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );

        self::assertInstanceOf(
            RsaSigner::class,
            $signer,
        );
    }

    public function testCreateRsaThrowsForNonRsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        SignerFactory::createRsa(
            algorithm: Algorithm::RS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );
    }

    public function testCreateEcdsaReturnsSignerForEcdsaPrivateKey(): void
    {
        $signer = SignerFactory::createEcdsa(
            algorithm: Algorithm::ES384,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP384PrivatePem(),
            ),
        );

        self::assertInstanceOf(
            EcdsaSigner::class,
            $signer,
        );
    }

    public function testCreateEcdsaThrowsForNonEcdsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        SignerFactory::createEcdsa(
            algorithm: Algorithm::ES256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );
    }

    public function testCreateEdDsaReturnsSignerForEdDsaPrivateKey(): void
    {
        $signer = SignerFactory::createEdDsa(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        self::assertInstanceOf(
            EdDsaSigner::class,
            $signer,
        );
    }

    public function testCreateEdDsaThrowsForNonEdDsaPrivateKey(): void
    {
        $this->expectException(JwtException::class);

        SignerFactory::createEdDsa(
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );
    }
}
