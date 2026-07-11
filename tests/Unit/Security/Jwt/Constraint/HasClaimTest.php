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
use Tuxxedo\Security\Jwt\Constraint\HasClaim;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class HasClaimTest extends TestCase
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

    public function testCheckPassesWhenPredicateAcceptsValue(): void
    {
        $constraint = new HasClaim(
            claim: 'role',
            predicate: static fn (mixed $value): bool => $value === 'admin',
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'role' => 'admin',
                ],
            ),
        );
    }

    public function testCheckThrowsWhenClaimIsMissing(): void
    {
        $constraint = new HasClaim(
            claim: 'role',
            predicate: static fn (mixed $value): bool => true,
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

    public function testCheckThrowsWhenPredicateRejects(): void
    {
        $constraint = new HasClaim(
            claim: 'role',
            predicate: static fn (mixed $value): bool => $value === 'admin',
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'role' => 'user',
                ],
            ),
        );
    }

    public function testCheckPassesForNullClaimWhenPredicateAcceptsNull(): void
    {
        $constraint = new HasClaim(
            claim: 'role',
            predicate: static fn (mixed $value): bool => $value === null,
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'role' => null,
                ],
            ),
        );
    }
}
