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
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\Constraint\EncryptedWith;
use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JweToken;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;

class EncryptedWithTest extends TestCase
{
    public function testCheckPassesForMatchingAlgAndEnc(): void
    {
        $token = new JweToken(
            header: new Header([
                'alg' => 'dir',
                'enc' => 'A256GCM',
            ]),
            claims: new Claims([]),
            encryptedKey: '',
            initializationVector: '',
            ciphertext: '',
            authenticationTag: '',
            compact: '',
        );

        $constraint = new EncryptedWith(
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenTokenIsJws(): void
    {
        $token = (new JwtManager())->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
        );

        $constraint = new EncryptedWith(
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsForAlgMismatch(): void
    {
        $token = new JweToken(
            header: new Header([
                'alg' => 'A128KW',
                'enc' => 'A256GCM',
            ]),
            claims: new Claims([]),
            encryptedKey: '',
            initializationVector: '',
            ciphertext: '',
            authenticationTag: '',
            compact: '',
        );

        $constraint = new EncryptedWith(
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsForEncMismatch(): void
    {
        $token = new JweToken(
            header: new Header([
                'alg' => 'dir',
                'enc' => 'A128GCM',
            ]),
            claims: new Claims([]),
            encryptedKey: '',
            initializationVector: '',
            ciphertext: '',
            authenticationTag: '',
            compact: '',
        );

        $constraint = new EncryptedWith(
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }

    public function testCheckThrowsWhenEncHeaderIsMissing(): void
    {
        $token = new JweToken(
            header: new Header([
                'alg' => 'dir',
            ]),
            claims: new Claims([]),
            encryptedKey: '',
            initializationVector: '',
            ciphertext: '',
            authenticationTag: '',
            compact: '',
        );

        $constraint = new EncryptedWith(
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $token,
        );
    }
}
