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
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Signer\RsaSigner;

class RsaSignerTest extends TestCase
{
    private function makeKey(): RsaPrivateKey
    {
        return new RsaPrivateKey(
            key: JwtKeyFixtures::rsaPrivatePem(),
        );
    }

    public function testSignProducesTwoHundredFiftySixBytesForRsa2048(): void
    {
        $signer = new RsaSigner(
            algorithm: Algorithm::RS256,
            key: $this->makeKey(),
        );

        self::assertSame(
            256,
            \strlen(
                $signer->sign(
                    payload: 'hello',
                ),
            ),
        );
    }

    public function testSignIsDeterministicForRs256(): void
    {
        $signer = new RsaSigner(
            algorithm: Algorithm::RS256,
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

    public function testSignProducesDifferentSignaturesForRs384And512(): void
    {
        $rs384 = new RsaSigner(
            algorithm: Algorithm::RS384,
            key: $this->makeKey(),
        );

        $rs512 = new RsaSigner(
            algorithm: Algorithm::RS512,
            key: $this->makeKey(),
        );

        self::assertNotSame(
            $rs384->sign(
                payload: 'payload',
            ),
            $rs512->sign(
                payload: 'payload',
            ),
        );
    }

    public function testConstructorThrowsForNonRsaAlgorithm(): void
    {
        $this->expectException(JwtException::class);

        new RsaSigner(
            algorithm: Algorithm::HS256,
            key: $this->makeKey(),
        );
    }
}
