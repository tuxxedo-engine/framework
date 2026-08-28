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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Security\Jwt\JwtKeyFixtures;
use Support\Temporal\FixedClock;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\Constraint\EncryptedWith;
use Tuxxedo\Security\Jwt\Constraint\IdentifiedBy;
use Tuxxedo\Security\Jwt\Constraint\IssuedBy;
use Tuxxedo\Security\Jwt\Constraint\PermittedFor;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\Constraint\ValidAt;
use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EcdsaPublicKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;
use Tuxxedo\Security\Jwt\Signer\EdDsaSigner;

class JwtManagerTest extends TestCase
{
    private function manager(): JwtManager
    {
        return new JwtManager();
    }

    private function hmacKey(
        ?string $keyId = null,
    ): SymmetricKey {
        return new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
            keyId: $keyId,
        );
    }

    public function testEncodeProducesCompactWithThreeSegments(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertCount(
            3,
            \explode('.', $token->compact),
        );
    }

    public function testEncodeSetsAlgHeader(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS384,
            key: $this->hmacKey(),
        );

        self::assertSame(
            'HS384',
            $token->header->algorithm,
        );
    }

    public function testEncodeSetsTypHeaderToJwt(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertSame(
            'JWT',
            $token->header->type,
        );
    }

    public function testEncodeIncludesKidWhenKeyHasKeyId(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(
                keyId: 'primary',
            ),
        );

        self::assertSame(
            'primary',
            $token->header->keyId,
        );
    }

    public function testEncodeOmitsKidWhenKeyHasNoKeyId(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertNull(
            $token->header->keyId,
        );
    }

    public function testEncodeMergesExtraHeaders(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
            extraHeader: [
                'custom' => 'value',
            ],
        );

        self::assertSame(
            'value',
            $token->header->get(
                header: 'custom',
            ),
        );
    }

    public function testEncodeAlgHeaderOverridesExtraHeader(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
            extraHeader: [
                'alg' => 'attacker-forged',
            ],
        );

        self::assertSame(
            'HS256',
            $token->header->algorithm,
        );
    }

    public function testEncodeThenParseRoundtripPreservesClaims(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
                'iss' => 'https://issuer.example',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $parsed = $manager->parse(
            compact: $encoded->compact,
        );

        self::assertSame(
            'user-1',
            $parsed->claims->subject,
        );

        self::assertSame(
            'https://issuer.example',
            $parsed->claims->issuer,
        );
    }

    public function testEncodeAndVerifyWithRsa(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
            ),
        );

        $decoded = $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::RS256,
                key: new RsaPublicKey(
                    key: JwtKeyFixtures::rsaPublicPem(),
                ),
            ),
        );

        self::assertSame(
            'user-1',
            $decoded->claims->subject,
        );
    }

    public function testEncodeAndVerifyWithEcdsa(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::ES256,
            key: new EcdsaPrivateKey(
                key: JwtKeyFixtures::ecdsaP256PrivatePem(),
            ),
        );

        $decoded = $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::ES256,
                key: new EcdsaPublicKey(
                    key: JwtKeyFixtures::ecdsaP256PublicPem(),
                ),
            ),
        );

        self::assertSame(
            'user-1',
            $decoded->claims->subject,
        );
    }

    public function testEncodeAndVerifyWithEdDsa(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::EDDSA,
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        $decoded = $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::EDDSA,
                key: new EdDsaPublicKey(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
            ),
        );

        self::assertSame(
            'user-1',
            $decoded->claims->subject,
        );
    }

    public function testParseThrowsForCompactWithWrongSegmentCount(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: 'only.two',
        );
    }

    public function testParseThrowsForInvalidBase64Header(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: '!!!invalid.eyJzdWIiOiJ1c2VyIn0.sig',
        );
    }

    public function testParseThrowsForNonJsonHeader(): void
    {
        $badHeader = \rtrim(
            \strtr(\base64_encode('not-json'), '+/', '-_'),
            '=',
        );

        $goodClaims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $badHeader . '.' . $goodClaims . '.sig',
        );
    }

    public function testParseThrowsWhenHeaderJsonIsListNotObject(): void
    {
        $listHeader = \rtrim(
            \strtr(\base64_encode('[1,2,3]'), '+/', '-_'),
            '=',
        );

        $goodClaims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $listHeader . '.' . $goodClaims . '.sig',
        );
    }

    public function testParseThrowsWhenHeaderMissesAlg(): void
    {
        $header = \rtrim(
            \strtr(\base64_encode('{"typ":"JWT"}'), '+/', '-_'),
            '=',
        );

        $claims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $header . '.' . $claims . '.sig',
        );
    }

    public function testDecodeThrowsWhenNoSignedWithConstraintPresent(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $this->expectException(JwtException::class);

        $manager->decode(
            $encoded->compact,
            new IdentifiedBy(
                id: 'anything',
            ),
        );
    }

    public function testDecodeAppliesAllConstraints(): void
    {
        $manager = $this->manager();
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
                'iss' => 'https://issuer.example',
                'aud' => ['api-service'],
                'jti' => 'token-42',
                'exp' => $clock->now()->getTimestamp() + 3600,
                'nbf' => $clock->now()->getTimestamp() - 60,
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $decoded = $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: $this->hmacKey(),
            ),
            new ValidAt(
                clock: $clock,
            ),
            new IssuedBy(
                'https://issuer.example',
            ),
            new PermittedFor(
                audience: 'api-service',
            ),
            new IdentifiedBy(
                id: 'token-42',
            ),
        );

        self::assertSame(
            'user-1',
            $decoded->claims->subject,
        );
    }

    public function testDecodeThrowsWhenAConstraintFails(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
                'iss' => 'https://wrong-issuer.example',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $this->expectException(JwtException::class);

        $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: $this->hmacKey(),
            ),
            new IssuedBy(
                'https://expected-issuer.example',
            ),
        );
    }

    public function testEncodeProducesTokenThatFailsDecodeWithWrongSecret(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $this->expectException(JwtException::class);

        $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: new SymmetricKey(
                    secret: 'not-the-secret',
                ),
            ),
        );
    }

    public function testTokenCompactMatchesSigningInputStructure(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $lastDot = \strrpos($token->compact, '.');

        self::assertNotFalse(
            $lastDot,
        );

        $signingInput = \substr($token->compact, 0, $lastDot);

        self::assertSame(
            2,
            \substr_count($signingInput, '.') + 1,
            'Signing input should be header.claims (two segments)',
        );
    }

    public function testEncodeWithUnicodeClaimSurvivesRoundtrip(): void
    {
        $manager = $this->manager();
        $encoded = $manager->encode(
            claims: [
                'sub' => 'ユーザー',
                'note' => 'æøå',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $parsed = $manager->parse(
            compact: $encoded->compact,
        );

        self::assertSame(
            'ユーザー',
            $parsed->claims->subject,
        );

        self::assertSame(
            'æøå',
            $parsed->claims->get(
                claim: 'note',
            ),
        );
    }

    public function testEncodeEmptyClaimsIsAllowed(): void
    {
        $token = $this->manager()->encode(
            claims: [],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertSame(
            [],
            $token->claims->all,
        );
    }

    public function testEncodeSignatureIsDeterministicForHmac(): void
    {
        $manager = $this->manager();
        $first = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        $second = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertSame(
            $first->compact,
            $second->compact,
        );
    }

    public function testParseThrowsForInvalidBase64Signature(): void
    {
        $header = \rtrim(
            \strtr(\base64_encode('{"alg":"HS256"}'), '+/', '-_'),
            '=',
        );

        $claims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $header . '.' . $claims . '.!!!invalid!!!',
        );
    }

    public function testParseThrowsWhenAlgHeaderIsEmptyString(): void
    {
        $header = \rtrim(
            \strtr(\base64_encode('{"alg":""}'), '+/', '-_'),
            '=',
        );

        $claims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $header . '.' . $claims . '.sig',
        );
    }

    public function testParseThrowsWhenAlgHeaderIsNotAString(): void
    {
        $header = \rtrim(
            \strtr(\base64_encode('{"alg":123}'), '+/', '-_'),
            '=',
        );

        $claims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $header . '.' . $claims . '.sig',
        );
    }

    public function testParseThrowsWhenDecodedJsonIsScalar(): void
    {
        $header = \rtrim(
            \strtr(\base64_encode('"just-a-string"'), '+/', '-_'),
            '=',
        );

        $claims = \rtrim(
            \strtr(\base64_encode('{"sub":"user"}'), '+/', '-_'),
            '=',
        );

        $this->expectException(JwtException::class);

        $this->manager()->parse(
            compact: $header . '.' . $claims . '.sig',
        );
    }

    public function testEncodeThrowsWhenClaimContainsInvalidUtf8(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->encode(
            claims: [
                'bad' => "\xB1\x31",
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );
    }

    public function testHeaderHasReturnsTrueForPresentKey(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertTrue(
            $token->header->has(
                header: 'alg',
            ),
        );
    }

    public function testHeaderHasReturnsFalseForMissingKey(): void
    {
        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertFalse(
            $token->header->has(
                header: 'nonexistent',
            ),
        );
    }

    public function testClaimsIssuedAtExposesIatTimestamp(): void
    {
        $issuedAt = 1_767_225_600;

        $token = $this->manager()->encode(
            claims: [
                'sub' => 'user-1',
                'iat' => $issuedAt,
            ],
            algorithm: Algorithm::HS256,
            key: $this->hmacKey(),
        );

        self::assertNotNull(
            $token->claims->issuedAt,
        );

        self::assertSame(
            $issuedAt,
            $token->claims->issuedAt->getTimestamp(),
        );
    }

    public function testEncodeEmitsCanonicalEdDsaSpellingInHeader(): void
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

        self::assertSame(
            'EdDSA',
            $token->header->algorithm,
        );
    }

    public function testDecodeAcceptsLegacyUppercaseEddsaHeader(): void
    {
        $legacyHeaderJson = '{"typ":"JWT","alg":"EDDSA"}';
        $legacyClaimsJson = '{"sub":"user-1"}';

        $encodedHeader = \rtrim(
            \strtr(\base64_encode($legacyHeaderJson), '+/', '-_'),
            '=',
        );

        $encodedClaims = \rtrim(
            \strtr(\base64_encode($legacyClaimsJson), '+/', '-_'),
            '=',
        );

        $signingInput = $encodedHeader . '.' . $encodedClaims;

        $signer = new EdDsaSigner(
            key: new EdDsaPrivateKey(
                bytes: JwtKeyFixtures::eddsaPrivateBytes(),
            ),
        );

        $encodedSignature = \rtrim(
            \strtr(
                \base64_encode(
                    $signer->sign(
                        payload: $signingInput,
                    ),
                ),
                '+/',
                '-_',
            ),
            '=',
        );

        $legacyCompact = $signingInput . '.' . $encodedSignature;

        $decoded = $this->manager()->decode(
            $legacyCompact,
            new SignedWith(
                algorithm: Algorithm::EDDSA,
                key: new EdDsaPublicKey(
                    bytes: JwtKeyFixtures::eddsaPublicBytes(),
                ),
            ),
        );

        self::assertSame(
            'EDDSA',
            $decoded->header->algorithm,
        );
    }

    public function testEncryptProducesCompactWithFiveSegments(): void
    {
        $token = $this->manager()->encrypt(
            claims: [
                'sub' => 'user-1',
            ],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 32),
            ),
        );

        self::assertCount(
            5,
            \explode('.', $token->compact),
        );
    }

    public function testEncryptSetsAlgAndEncHeaders(): void
    {
        $token = $this->manager()->encrypt(
            claims: [
                'sub' => 'user-1',
            ],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 32),
            ),
        );

        self::assertSame(
            'dir',
            $token->header->algorithm,
        );
        self::assertSame(
            'A256GCM',
            $token->header->get('enc'),
        );
    }

    public function testEncryptWithDirectAlgorithmThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );
    }

    public function testEncryptWithDirectAlgorithmThrowsForWrongKeyLength(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 16),
            ),
        );
    }

    public function testEncryptWithKeyWrapProducesNonEmptyEncryptedKey(): void
    {
        $token = $this->manager()->encrypt(
            claims: [
                'sub' => 'user-1',
            ],
            keyAlgorithm: KeyManagementAlgorithm::A128KW,
            contentAlgorithm: ContentEncryptionAlgorithm::A128GCM,
            key: new SymmetricKey(
                secret: \str_repeat("\x00", 16),
            ),
        );

        self::assertNotSame(
            '',
            $token->encryptedKey,
        );
    }

    public function testParseEncryptedThrowsForNonFiveSegmentInput(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->parseEncrypted(
            compact: 'a.b.c',
        );
    }

    /**
     * @return array<string, array{KeyManagementAlgorithm, ContentEncryptionAlgorithm, int}>
     */
    public static function providesEncryptDecryptRoundTripCombos(): array
    {
        return [
            'DIR + A128GCM' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A128GCM,
                16,
            ],
            'DIR + A192GCM' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A192GCM,
                24,
            ],
            'DIR + A256GCM' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A256GCM,
                32,
            ],
            'DIR + A128CBC-HS256' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A128CBC_HS256,
                32,
            ],
            'DIR + A192CBC-HS384' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A192CBC_HS384,
                48,
            ],
            'DIR + A256CBC-HS512' => [
                KeyManagementAlgorithm::DIR,
                ContentEncryptionAlgorithm::A256CBC_HS512,
                64,
            ],
            'A128KW + A128GCM' => [
                KeyManagementAlgorithm::A128KW,
                ContentEncryptionAlgorithm::A128GCM,
                16,
            ],
            'A192KW + A192GCM' => [
                KeyManagementAlgorithm::A192KW,
                ContentEncryptionAlgorithm::A192GCM,
                24,
            ],
            'A256KW + A256CBC-HS512' => [
                KeyManagementAlgorithm::A256KW,
                ContentEncryptionAlgorithm::A256CBC_HS512,
                32,
            ],
        ];
    }

    #[DataProvider('providesEncryptDecryptRoundTripCombos')]
    public function testEncryptDecryptRoundTripsForAlgorithmCombo(
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        int $keyLength,
    ): void {
        $key = new SymmetricKey(
            secret: \str_repeat("\x11", $keyLength),
        );

        $token = $this->manager()->encrypt(
            claims: [
                'sub' => 'user-1',
            ],
            keyAlgorithm: $keyAlgorithm,
            contentAlgorithm: $contentAlgorithm,
            key: $key,
        );

        $decrypted = $this->manager()->decrypt(
            $token->compact,
            $key,
            new EncryptedWith(
                keyAlgorithm: $keyAlgorithm,
                contentAlgorithm: $contentAlgorithm,
            ),
        );

        self::assertSame(
            'user-1',
            $decrypted->claims->get('sub'),
        );
    }

    public function testEncryptWithKeyWrapAlgorithmThrowsForNonSymmetricKey(): void
    {
        $this->expectException(JwtException::class);

        $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::A128KW,
            contentAlgorithm: ContentEncryptionAlgorithm::A128GCM,
            key: new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
        );
    }

    public function testDecryptWithKeyWrapAlgorithmThrowsForNonSymmetricKey(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 16),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::A128KW,
            contentAlgorithm: ContentEncryptionAlgorithm::A128GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::A128KW,
                contentAlgorithm: ContentEncryptionAlgorithm::A128GCM,
            ),
        );
    }

    public function testDecryptThrowsWhenEncryptedWithConstraintIsMissing(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            $key,
        );
    }

    public function testDecryptThrowsForAlgMismatch(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            $key,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::A128KW,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
        );
    }

    public function testDecryptThrowsForEncMismatch(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            $key,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A128GCM,
            ),
        );
    }

    public function testDecryptWithDirAlgorithmThrowsForNonSymmetricKey(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            new EdDsaPublicKey(
                bytes: JwtKeyFixtures::eddsaPublicBytes(),
            ),
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
        );
    }

    public function testDecryptWithDirAlgorithmThrowsForWrongKeyLength(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x00", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            new SymmetricKey(
                secret: \str_repeat("\x00", 16),
            ),
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
        );
    }

    public function testDecryptRunsAdditionalConstraintsAfterDecryption(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x44", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [
                'iss' => 'issuer-a',
            ],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decrypt(
            $token->compact,
            $key,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
            new IssuedBy(
                issuer: 'issuer-b',
            ),
        );
    }

    public function testEncodeAndEncryptRoundTripsThroughDecryptAndDecode(): void
    {
        $signingKey = new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
        $encryptionKey = new SymmetricKey(
            secret: \str_repeat("\x55", 32),
        );

        $token = $this->manager()->encodeAndEncrypt(
            claims: [
                'sub' => 'user-nested',
            ],
            signingAlgorithm: Algorithm::HS256,
            signingKey: $signingKey,
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            encryptionKey: $encryptionKey,
        );

        $inner = $this->manager()->decryptAndDecode(
            $token->compact,
            $encryptionKey,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: $signingKey,
            ),
        );

        self::assertSame(
            'user-nested',
            $inner->claims->get('sub'),
        );
    }

    public function testEncodeAndEncryptSetsCtyHeaderOnOuterJwe(): void
    {
        $token = $this->manager()->encodeAndEncrypt(
            claims: [],
            signingAlgorithm: Algorithm::HS256,
            signingKey: new SymmetricKey(
                secret: JwtKeyFixtures::hmacSecretBytes(),
            ),
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            encryptionKey: new SymmetricKey(
                secret: \str_repeat("\x00", 32),
            ),
        );

        self::assertSame(
            'JWT',
            $token->header->get('cty'),
        );
    }

    public function testDecryptAndDecodeThrowsWhenOuterCtyIsMissing(): void
    {
        $key = new SymmetricKey(
            secret: \str_repeat("\x66", 32),
        );

        $token = $this->manager()->encrypt(
            claims: [
                'sub' => 'x',
            ],
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            key: $key,
        );

        $this->expectException(JwtException::class);

        $this->manager()->decryptAndDecode(
            $token->compact,
            $key,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: new SymmetricKey(
                    secret: JwtKeyFixtures::hmacSecretBytes(),
                ),
            ),
        );
    }

    public function testDecryptAndDecodeAcceptsLowercaseCtyForCompatibility(): void
    {
        $signingKey = new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
        $encryptionKey = new SymmetricKey(
            secret: \str_repeat("\x77", 32),
        );

        $token = $this->manager()->encodeAndEncrypt(
            claims: [
                'sub' => 'lower',
            ],
            signingAlgorithm: Algorithm::HS256,
            signingKey: $signingKey,
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            encryptionKey: $encryptionKey,
            extraHeader: [
                'cty' => 'jwt',
            ],
        );

        $inner = $this->manager()->decryptAndDecode(
            $token->compact,
            $encryptionKey,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: $signingKey,
            ),
        );

        self::assertSame(
            'lower',
            $inner->claims->get('sub'),
        );
    }

    public function testDecryptAndDecodeFiltersEncryptedWithFromInnerConstraints(): void
    {
        $signingKey = new SymmetricKey(
            secret: JwtKeyFixtures::hmacSecretBytes(),
        );
        $encryptionKey = new SymmetricKey(
            secret: \str_repeat("\x88", 32),
        );

        $token = $this->manager()->encodeAndEncrypt(
            claims: [
                'sub' => 'filter-test',
            ],
            signingAlgorithm: Algorithm::HS256,
            signingKey: $signingKey,
            keyAlgorithm: KeyManagementAlgorithm::DIR,
            contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            encryptionKey: $encryptionKey,
        );

        $inner = $this->manager()->decryptAndDecode(
            $token->compact,
            $encryptionKey,
            new EncryptedWith(
                keyAlgorithm: KeyManagementAlgorithm::DIR,
                contentAlgorithm: ContentEncryptionAlgorithm::A256GCM,
            ),
            new SignedWith(
                algorithm: Algorithm::HS256,
                key: $signingKey,
            ),
        );

        self::assertSame(
            'filter-test',
            $inner->claims->get('sub'),
        );
    }
}
