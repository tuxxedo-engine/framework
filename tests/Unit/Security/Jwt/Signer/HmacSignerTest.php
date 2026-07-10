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
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Signer\HmacSigner;

class HmacSignerTest extends TestCase
{
    private function makeKey(): SymmetricKey
    {
        return new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
    }

    public function testSignProducesThirtyTwoBytesForHs256(): void
    {
        $signer = new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );

        self::assertSame(
            32,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignProducesFortyEightBytesForHs384(): void
    {
        $signer = new HmacSigner(
            algorithm: Algorithm::HS384,
            key: $this->makeKey(),
        );

        self::assertSame(
            48,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignProducesSixtyFourBytesForHs512(): void
    {
        $signer = new HmacSigner(
            algorithm: Algorithm::HS512,
            key: $this->makeKey(),
        );

        self::assertSame(
            64,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignIsDeterministic(): void
    {
        $signer = new HmacSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );

        self::assertSame(
            $signer->sign(
                payload: 'same input',
            ),
            $signer->sign(
                payload: 'same input',
            ),
        );
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $first = new HmacSigner(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: 'secret-one',
            ),
        );

        $second = new HmacSigner(
            algorithm: Algorithm::HS256,
            key: new SymmetricKey(
                secret: 'secret-two',
            ),
        );

        self::assertNotSame(
            $first->sign(
                payload: 'shared payload',
            ),
            $second->sign(
                payload: 'shared payload',
            ),
        );
    }

    public function testConstructorThrowsForNonHmacAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new HmacSigner(
            algorithm: Algorithm::RS256,
            key: $this->makeKey(),
        );
    }
}
