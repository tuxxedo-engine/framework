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

namespace Unit\Http\Kernel\Resolver;

use PHPUnit\Framework\TestCase;
use Support\Reflection\StubParameterReflector;
use Support\Security\Jwt\StubJwtTokenAccessor;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Kernel\Resolver\JwtClaims as JwtClaimsResolver;
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\ClaimsInterface;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class JwtClaimsTest extends TestCase
{
    private function containerWith(
        ?TokenInterface $token,
    ): Container {
        $container = new Container();

        $container->singleton(
            class: new StubJwtTokenAccessor(
                token: $token,
            ),
        );

        return $container;
    }

    private function makeToken(
        ClaimsInterface $claims,
    ): TokenInterface {
        return new Token(
            header: new Header(
                all: [
                    'alg' => 'HS256',
                    'typ' => 'JWT',
                ],
            ),
            claims: $claims,
            signature: 'sig-bytes',
            compact: 'header.claims.signature',
        );
    }

    public function testResolveReturnsClaimsFromCurrentToken(): void
    {
        $claims = new Claims(
            all: [
                'sub' => 'user-1',
                'iss' => 'https://issuer.example',
            ],
        );

        $resolved = (new JwtClaimsResolver())->resolve(
            container: $this->containerWith(
                token: $this->makeToken(
                    claims: $claims,
                ),
            ),
            parameter: new StubParameterReflector(
                defaultType: ClaimsInterface::class,
            ),
        );

        self::assertSame(
            $claims,
            $resolved,
        );
    }

    public function testResolveReturnsNullWhenAccessorEmptyAndParameterIsNullable(): void
    {
        self::assertNull(
            (new JwtClaimsResolver())->resolve(
                container: $this->containerWith(
                    token: null,
                ),
                parameter: new StubParameterReflector(
                    defaultType: ClaimsInterface::class,
                    nullable: true,
                ),
            ),
        );
    }

    public function testResolveThrowsWhenAccessorEmptyAndParameterIsRequired(): void
    {
        $this->expectException(JwtException::class);

        (new JwtClaimsResolver())->resolve(
            container: $this->containerWith(
                token: null,
            ),
            parameter: new StubParameterReflector(
                defaultType: ClaimsInterface::class,
            ),
        );
    }
}
