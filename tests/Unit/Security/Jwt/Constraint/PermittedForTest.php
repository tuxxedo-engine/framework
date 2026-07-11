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
use Tuxxedo\Security\Jwt\Constraint\PermittedFor;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class PermittedForTest extends TestCase
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

    public function testCheckPassesWhenAudienceIsList(): void
    {
        $constraint = new PermittedFor(
            audience: 'api-service',
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'aud' => ['api-service', 'other-service'],
                ],
            ),
        );
    }

    public function testCheckPassesWhenAudienceIsStringScalar(): void
    {
        $constraint = new PermittedFor(
            audience: 'api-service',
        );

        self::expectNotToPerformAssertions();

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'aud' => 'api-service',
                ],
            ),
        );
    }

    public function testCheckThrowsWhenAudienceIsMissing(): void
    {
        $constraint = new PermittedFor(
            audience: 'api-service',
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

    public function testCheckThrowsWhenAudienceIsNotInList(): void
    {
        $constraint = new PermittedFor(
            audience: 'api-service',
        );

        $this->expectException(JwtException::class);

        $constraint->check(
            token: $this->makeToken(
                claims: [
                    'aud' => ['other-service'],
                ],
            ),
        );
    }
}
