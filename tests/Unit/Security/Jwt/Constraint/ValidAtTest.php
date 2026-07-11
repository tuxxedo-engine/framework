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
use Support\Temporal\FixedClock;
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\Constraint\ValidAt;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class ValidAtTest extends TestCase
{
    /**
     * @param array<string, mixed> $claims
     */
    private function makeToken(
        array $claims,
    ): TokenInterface {
        return new Token(
            header: new Header(
                all: [
                    'alg' => 'HS256',
                    'typ' => 'JWT',
                ],
            ),
            claims: new Claims(
                all: $claims,
            ),
            signature: 'x',
            compact: 'x.y.z',
        );
    }

    public function testCheckPassesWhenExpiryIsInTheFuture(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'exp' => $clock->now()->getTimestamp() + 60,
                ],
            ),
        );
    }

    public function testCheckThrowsWhenTokenExpired(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'exp' => $clock->now()->getTimestamp() - 60,
                ],
            ),
        );
    }

    public function testExpiredTokenPassesWithLeeway(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
            leewaySeconds: 120,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'exp' => $clock->now()->getTimestamp() - 60,
                ],
            ),
        );
    }

    public function testCheckPassesWhenNotBeforeIsInThePast(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'nbf' => $clock->now()->getTimestamp() - 60,
                ],
            ),
        );
    }

    public function testCheckThrowsWhenTokenNotYetValid(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'nbf' => $clock->now()->getTimestamp() + 60,
                ],
            ),
        );
    }

    public function testNotYetValidTokenPassesWithLeeway(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
            leewaySeconds: 120,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'nbf' => $clock->now()->getTimestamp() + 60,
                ],
            ),
        );
    }

    public function testCheckIgnoresMissingExpAndNbf(): void
    {
        $clock = new FixedClock(
            now: new \DateTimeImmutable(
                datetime: '2026-01-01T00:00:00Z',
            ),
        );

        $constraint = new ValidAt(
            clock: $clock,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'sub' => 'user-1',
                ],
            ),
        );
    }
}
