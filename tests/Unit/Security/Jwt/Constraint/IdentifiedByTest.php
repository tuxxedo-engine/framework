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
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\Constraint\IdentifiedBy;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class IdentifiedByTest extends TestCase
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
                ],
            ),
            claims: new Claims(
                all: $claims,
            ),
            signature: 'x',
            compact: 'x.y.z',
        );
    }

    public function testCheckPassesForMatchingId(): void
    {
        $constraint = new IdentifiedBy(
            id: 'token-42',
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'jti' => 'token-42',
                ],
            ),
        );
    }

    public function testCheckThrowsForMismatchedId(): void
    {
        $constraint = new IdentifiedBy(
            id: 'token-42',
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'jti' => 'token-99',
                ],
            ),
        );
    }

    public function testCheckThrowsWhenJtiClaimIsMissing(): void
    {
        $constraint = new IdentifiedBy(
            id: 'token-42',
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'sub' => 'user-1',
                ],
            ),
        );
    }
}
