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

namespace Unit\Http\Client\Auth;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Auth\AccessToken;
use Tuxxedo\Temporal\Instant;

class AccessTokenTest extends TestCase
{
    public function testDefaults(): void
    {
        $token = new AccessToken('abc');

        self::assertSame('abc', $token->accessToken);
        self::assertSame('Bearer', $token->tokenType);
        self::assertNull($token->expiresAt);
        self::assertNull($token->refreshToken);
        self::assertFalse($token->isExpired(Instant::fromUnixTimestamp(4_000_000_000)));
    }

    public function testExpiresWithLeeway(): void
    {
        $token = new AccessToken(
            accessToken: 'abc',
            expiresAt: Instant::fromUnixTimestamp(1_000),
        );

        self::assertFalse($token->isExpired(Instant::fromUnixTimestamp(969)));
        self::assertTrue($token->isExpired(Instant::fromUnixTimestamp(970)));
        self::assertTrue($token->isExpired(Instant::fromUnixTimestamp(2_000)));
    }
}
