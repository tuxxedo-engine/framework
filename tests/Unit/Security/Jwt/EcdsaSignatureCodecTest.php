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

namespace Unit\Security\Jwt;

use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Tuxxedo\Security\Jwt\EcdsaSignatureCodec;
use Tuxxedo\Security\Jwt\JwtException;

class EcdsaSignatureCodecTest extends TestCase
{
    private const int P256_COMPONENT_LENGTH = 32;
    private const int P384_COMPONENT_LENGTH = 48;
    private const int P521_COMPONENT_LENGTH = 66;

    /**
     * @return non-empty-string
     */
    private function opensslSign(
        string $privatePem,
        int $algorithm,
        string $payload,
    ): string {
        $key = \openssl_pkey_get_private($privatePem);

        if ($key === false) {
            self::fail('Failed to parse ECDSA private key fixture');
        }

        $signature = null;

        if (!\openssl_sign($payload, $signature, $key, $algorithm)) {
            self::fail('openssl_sign failed');
        }

        if (!\is_string($signature) || $signature === '') {
            self::fail('openssl_sign produced no signature');
        }

        return $signature;
    }

    private function opensslVerify(
        string $publicPem,
        int $algorithm,
        string $payload,
        string $der,
    ): bool {
        $key = \openssl_pkey_get_public($publicPem);

        if ($key === false) {
            self::fail('Failed to parse ECDSA public key fixture');
        }

        return \openssl_verify($payload, $der, $key, $algorithm) === 1;
    }

    public function testDerToJoseProducesFixedComponentLengthForP256(): void
    {
        $der = $this->opensslSign(
            privatePem: JwtKeyFixtures::ecdsaP256PrivatePem(),
            algorithm: \OPENSSL_ALGO_SHA256,
            payload: 'hello',
        );

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $jose = $codec->derToJose(
            der: $der,
        );

        self::assertSame(
            self::P256_COMPONENT_LENGTH * 2,
            \strlen($jose),
        );
    }

    public function testDerToJoseProducesFixedComponentLengthForP384(): void
    {
        $der = $this->opensslSign(
            privatePem: JwtKeyFixtures::ecdsaP384PrivatePem(),
            algorithm: \OPENSSL_ALGO_SHA384,
            payload: 'hello',
        );

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES384',
            componentLength: self::P384_COMPONENT_LENGTH,
        );

        self::assertSame(
            self::P384_COMPONENT_LENGTH * 2,
            \strlen(
                $codec->derToJose(
                    der: $der,
                ),
            ),
        );
    }

    public function testDerToJoseProducesFixedComponentLengthForP521(): void
    {
        $der = $this->opensslSign(
            privatePem: JwtKeyFixtures::ecdsaP521PrivatePem(),
            algorithm: \OPENSSL_ALGO_SHA512,
            payload: 'hello',
        );

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES512',
            componentLength: self::P521_COMPONENT_LENGTH,
        );

        self::assertSame(
            self::P521_COMPONENT_LENGTH * 2,
            \strlen(
                $codec->derToJose(
                    der: $der,
                ),
            ),
        );
    }

    public function testRoundTripVerifiesWithOpenssl(): void
    {
        $payload = 'the quick brown fox';
        $der = $this->opensslSign(
            privatePem: JwtKeyFixtures::ecdsaP256PrivatePem(),
            algorithm: \OPENSSL_ALGO_SHA256,
            payload: $payload,
        );

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $roundTrippedDer = $codec->joseToDer(
            jose: $codec->derToJose(
                der: $der,
            ),
        );

        self::assertTrue(
            $this->opensslVerify(
                publicPem: JwtKeyFixtures::ecdsaP256PublicPem(),
                algorithm: \OPENSSL_ALGO_SHA256,
                payload: $payload,
                der: $roundTrippedDer,
            ),
        );
    }

    public function testDerToJoseThrowsWhenSequenceHeaderIsMissing(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $this->expectException(JwtException::class);

        $codec->derToJose(
            der: "\x00\x00",
        );
    }

    public function testDerToJoseThrowsWhenFirstIntegerTagIsMissing(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $this->expectException(JwtException::class);

        $codec->derToJose(
            der: "\x30\x04\xFF\x01\x00\x00",
        );
    }

    public function testDerToJoseThrowsWhenSecondIntegerTagIsMissing(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $this->expectException(JwtException::class);

        $codec->derToJose(
            der: "\x30\x06\x02\x01\x01\xFF\x01\x00",
        );
    }

    public function testJoseToDerStartsWithSequenceTag(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $jose = \str_repeat("\x01", self::P256_COMPONENT_LENGTH * 2);

        $der = $codec->joseToDer(
            jose: $jose,
        );

        self::assertSame(
            "\x30",
            $der[0],
        );
    }

    public function testJoseToDerPrependsLeadingZeroForHighBitInteger(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $highBitR = \str_repeat("\xFF", self::P256_COMPONENT_LENGTH);
        $lowBitS = \str_repeat("\x01", self::P256_COMPONENT_LENGTH);

        $der = $codec->joseToDer(
            jose: $highBitR . $lowBitS,
        );

        $firstIntegerTagPosition = 2;

        self::assertSame(
            "\x02",
            $der[$firstIntegerTagPosition],
        );

        self::assertSame(
            "\x00",
            $der[$firstIntegerTagPosition + 2],
        );
    }

    public function testJoseToDerHandlesZeroPaddedComponents(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $paddedR = \str_repeat("\x00", self::P256_COMPONENT_LENGTH - 1) . "\x01";
        $paddedS = \str_repeat("\x00", self::P256_COMPONENT_LENGTH - 1) . "\x01";

        $der = $codec->joseToDer(
            jose: $paddedR . $paddedS,
        );

        self::assertSame(
            "\x30\x06\x02\x01\x01\x02\x01\x01",
            $der,
        );
    }

    public function testJoseToDerCollapsesAllZeroFirstIntegerToSingleZeroByte(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $zeroR = \str_repeat("\x00", self::P256_COMPONENT_LENGTH);
        $nonZeroS = \str_repeat("\x00", self::P256_COMPONENT_LENGTH - 1) . "\x01";

        $der = $codec->joseToDer(
            jose: $zeroR . $nonZeroS,
        );

        self::assertSame(
            "\x30\x06\x02\x01\x00\x02\x01\x01",
            $der,
        );
    }

    public function testJoseToDerCollapsesAllZeroSecondIntegerToSingleZeroByte(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $nonZeroR = \str_repeat("\x00", self::P256_COMPONENT_LENGTH - 1) . "\x01";
        $zeroS = \str_repeat("\x00", self::P256_COMPONENT_LENGTH);

        $der = $codec->joseToDer(
            jose: $nonZeroR . $zeroS,
        );

        self::assertSame(
            "\x30\x06\x02\x01\x01\x02\x01\x00",
            $der,
        );
    }

    public function testJoseToDerPrependsLeadingZeroForHighBitSecondInteger(): void
    {
        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES256',
            componentLength: self::P256_COMPONENT_LENGTH,
        );

        $lowBitR = \str_repeat("\x01", self::P256_COMPONENT_LENGTH);
        $highBitS = \str_repeat("\xFF", self::P256_COMPONENT_LENGTH);

        $der = $codec->joseToDer(
            jose: $lowBitR . $highBitS,
        );

        $firstIntegerLength = \ord($der[3]);
        $secondIntegerTagPosition = 2 + 2 + $firstIntegerLength;

        self::assertSame(
            "\x02",
            $der[$secondIntegerTagPosition],
        );

        self::assertSame(
            "\x00",
            $der[$secondIntegerTagPosition + 2],
        );
    }

    public function testJoseToDerUsesLongFormLengthForIntegerBody(): void
    {
        $componentLength = 128;

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES-fake-128',
            componentLength: $componentLength,
        );

        $jose = \str_repeat("\x01", $componentLength * 2);

        $der = $codec->joseToDer(
            jose: $jose,
        );

        self::assertSame(
            "\x02",
            $der[4],
        );

        self::assertSame(
            "\x81",
            $der[5],
        );

        self::assertSame(
            "\x80",
            $der[6],
        );
    }

    public function testJoseToDerUsesTwoByteFormLengthForSequenceContent(): void
    {
        $componentLength = 128;

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES-fake-128',
            componentLength: $componentLength,
        );

        $der = $codec->joseToDer(
            jose: \str_repeat("\x01", $componentLength * 2),
        );

        self::assertSame(
            "\x82",
            $der[1],
        );
    }

    public function testJoseToDerThrowsWhenSequenceContentExceedsTwoByteLength(): void
    {
        $componentLength = 32770;

        $codec = new EcdsaSignatureCodec(
            algorithmIdentifier: 'ES-fake-huge',
            componentLength: $componentLength,
        );

        $this->expectException(JwtException::class);

        $codec->joseToDer(
            jose: \str_repeat("\x01", $componentLength * 2),
        );
    }
}
