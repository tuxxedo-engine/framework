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

namespace Unit\Security\Jwt\Constraint;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\KeySet;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class SignedWithTest extends TestCase
{
    private function manager(): JwtManager
    {
        return new JwtManager();
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function makeHs256Token(
        array $claims = ['sub' => 'user-1'],
        ?string $keyId = null,
    ): TokenInterface {
        return $this->manager()->encode(
            claims: $claims,
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
                keyId: $keyId,
            ),
        );
    }

    public function testCheckPassesForMatchingSymmetricKey(): void
    {
        $token = $this->makeHs256Token();

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckPassesForMatchingRsaPublicKey(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaPublicPem(),
            ),
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenAlgorithmMismatches(): void
    {
        $token = $this->makeHs256Token();

        $constraint = new SignedWith(
            algorithm: Algorithm::HS512,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenSignatureIsWrong(): void
    {
        $token = $this->makeHs256Token();

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: 'wrong-secret',
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenPublicKeyIsWrongForRsa(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::RS256,
            key: new RsaPublicKey(
                key: JwtKeyFixtures::rsaOtherPublicPem(),
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenPublicKeyIsWrongForEcdsa(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::ES256,
            key: new EcdsaPublicKey(
                key: JwtKeyFixtures::ecdsaP256OtherPublicPem(),
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenPublicKeyIsWrongForEdDsa(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::EDDSA,
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::EDDSA,
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaOtherPublicBytes(),
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenCompactIsMissingDots(): void
    {
        $realToken = $this->makeHs256Token();

        $token = new Token(
            header: $realToken->header,
            claims: $realToken->claims,
            signature: $realToken->signature,
            compact: 'nodothere',
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckWithKeySetResolvesByKid(): void
    {
        $token = $this->makeHs256Token(
            keyId: 'primary',
        );

        $keySet = new KeySet(
            new SymmetricKey(
                secret: 'ignored',
                keyId: 'secondary',
            ),
            new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
                keyId: 'primary',
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: $keySet,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckWithKeySetThrowsWhenKidHeaderMissing(): void
    {
        $token = $this->makeHs256Token();

        $keySet = new KeySet(
            new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
                keyId: 'primary',
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: $keySet,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckWithKeySetThrowsWhenNoMatchingKey(): void
    {
        $token = $this->makeHs256Token(
            keyId: 'unknown',
        );

        $keySet = new KeySet(
            new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
                keyId: 'primary',
            ),
        );

        $constraint = new SignedWith(
            algorithm: Algorithm::HS256,
            key: $keySet,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }
}
