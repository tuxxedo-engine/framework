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
use Support\Security\Jwt\OpensslKeyComponents;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\JwksParser;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManager;
use Tuxxedo\Security\Jwt\Key\KeySet;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class JwksParserTest extends TestCase
{
    private function base64UrlEncode(
        string $bytes,
    ): string {
        return \rtrim(
            \strtr(\base64_encode($bytes), '+/', '-_'),
            '=',
        );
    }

    /**
     * @return array<string, string>
     */
    private function octJwk(
        string $kid,
    ): array {
        return [
            'kty' => 'oct',
            'kid' => $kid,
            'k' => $this->base64UrlEncode(
                bytes: JwtKeyFixtures::hmacSecretBytes(),
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rsaPublicJwk(
        string $kid,
    ): array {
        $components = OpensslKeyComponents::rsaPublic(
            pem: JwtKeyFixtures::rsaPublicPem(),
        );

        return [
            'kty' => 'RSA',
            'kid' => $kid,
            'n' => $this->base64UrlEncode(
                bytes: $components['n'],
            ),
            'e' => $this->base64UrlEncode(
                bytes: $components['e'],
            ),
        ];
    }

    /**
     * @param list<array<string, mixed>> $keys
     */
    private function encodeJwks(
        array $keys,
    ): string {
        return \json_encode(
            value: [
                'keys' => $keys,
            ],
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    public function testParseReturnsEmptyKeySetForEmptyKeysArray(): void
    {
        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [],
            ),
        );

        self::assertInstanceOf(
            KeySet::class,
            $set,
        );

        self::assertSame(
            [],
            $set->keys,
        );
    }

    public function testParseReturnsKeySetForSingleOctJwk(): void
    {
        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                ],
            ),
        );

        self::assertCount(
            1,
            $set->keys,
        );

        self::assertInstanceOf(
            SymmetricKey::class,
            $set->keys[0],
        );

        self::assertSame(
            'hmac-1',
            $set->keys[0]->keyId,
        );
    }

    public function testParseReturnsKeySetWithMultipleMixedKtyKeys(): void
    {
        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                    $this->rsaPublicJwk(
                        kid: 'rsa-1',
                    ),
                ],
            ),
        );

        self::assertCount(
            2,
            $set->keys,
        );

        self::assertInstanceOf(
            SymmetricKey::class,
            $set->keys[0],
        );

        self::assertInstanceOf(
            RsaPublicKey::class,
            $set->keys[1],
        );
    }

    public function testParsedKeySetResolvesKeyByKid(): void
    {
        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                    $this->rsaPublicJwk(
                        kid: 'rsa-1',
                    ),
                ],
            ),
        );

        $found = $set->find(
            keyId: 'rsa-1',
        );

        self::assertInstanceOf(
            RsaPublicKey::class,
            $found,
        );

        self::assertSame(
            'rsa-1',
            $found->keyId,
        );
    }

    public function testParsedKeySetReturnsNullForUnknownKid(): void
    {
        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                ],
            ),
        );

        self::assertNull(
            $set->find(
                keyId: 'nonexistent',
            ),
        );
    }

    public function testParseFailsClosedWhenAnyEntryIsMalformed(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                    [
                        'kty' => 'RSA',
                    ],
                ],
            ),
        );
    }

    public function testParseThrowsForInvalidJson(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '{not json',
        );
    }

    public function testParseThrowsWhenTopLevelIsScalar(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '"just a string"',
        );
    }

    public function testParseThrowsWhenTopLevelIsJsonArray(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '[1, 2, 3]',
        );
    }

    public function testParseThrowsWhenKeysFieldMissing(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '{"not_keys": []}',
        );
    }

    public function testParseThrowsWhenKeysFieldIsNotArray(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '{"keys": "wrong"}',
        );
    }

    public function testParseThrowsWhenKeysFieldIsJsonObject(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '{"keys": {"kid1": {"kty": "oct", "k": "AAAA"}}}',
        );
    }

    public function testParseThrowsWhenEntryIsNotAnObject(): void
    {
        $this->expectException(JwtException::class);

        JwksParser::parse(
            json: '{"keys": ["not an object"]}',
        );
    }

    public function testDecodeVerifiesTokenViaSignedWithFromParsedJwks(): void
    {
        $manager = new JwtManager();

        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
                keyId: 'rsa-1',
            ),
        );

        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->octJwk(
                        kid: 'hmac-1',
                    ),
                    $this->rsaPublicJwk(
                        kid: 'rsa-1',
                    ),
                ],
            ),
        );

        $decoded = $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::RS256,
                key: $set,
            ),
        );

        self::assertSame(
            'user-1',
            $decoded->claims->subject,
        );
    }

    public function testDecodeFailsWhenKidDoesNotResolveInJwks(): void
    {
        $manager = new JwtManager();

        $encoded = $manager->encode(
            claims: [
                'sub' => 'user-1',
            ],
            algorithm: Algorithm::RS256,
            key: new RsaPrivateKey(
                key: JwtKeyFixtures::rsaPrivatePem(),
                keyId: 'unknown-kid',
            ),
        );

        $set = JwksParser::parse(
            json: $this->encodeJwks(
                keys: [
                    $this->rsaPublicJwk(
                        kid: 'known-kid',
                    ),
                ],
            ),
        );

        $this->expectException(JwtException::class);

        $manager->decode(
            $encoded->compact,
            new SignedWith(
                algorithm: Algorithm::RS256,
                key: $set,
            ),
        );
    }
}
