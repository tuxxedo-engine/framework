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
use Tuxxedo\Http\Kernel\Resolver\Jwt as JwtResolver;
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwsTokenInterface;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Token;

class JwtTest extends TestCase
{
    private function containerWith(
        ?JwsTokenInterface $token,
    ): Container {
        $container = new Container();

        $container->singleton(
            class: new StubJwtTokenAccessor(
                token: $token,
            ),
        );

        return $container;
    }

    private function makeToken(): JwsTokenInterface
    {
        return new Token(
            header: new Header(
                all: [
                    'alg' => 'HS256',
                    'typ' => 'JWT',
                ],
            ),
            claims: new Claims(
                all: [
                    'sub' => 'user-1',
                ],
            ),
            signature: 'sig-bytes',
            compact: 'header.claims.signature',
        );
    }

    public function testResolveReturnsCurrentToken(): void
    {
        $token = $this->makeToken();

        self::assertSame(
            $token,
            (new JwtResolver())->resolve(
                container: $this->containerWith(
                    token: $token,
                ),
                parameter: new StubParameterReflector(
                    defaultType: JwsTokenInterface::class,
                ),
            ),
        );
    }

    public function testResolveReturnsNullWhenAccessorEmptyAndParameterIsNullable(): void
    {
        self::assertNull(
            (new JwtResolver())->resolve(
                container: $this->containerWith(
                    token: null,
                ),
                parameter: new StubParameterReflector(
                    defaultType: JwsTokenInterface::class,
                    nullable: true,
                ),
            ),
        );
    }

    public function testResolveThrowsWhenAccessorEmptyAndParameterIsRequired(): void
    {
        $this->expectException(JwtException::class);

        (new JwtResolver())->resolve(
            container: $this->containerWith(
                token: null,
            ),
            parameter: new StubParameterReflector(
                defaultType: JwsTokenInterface::class,
            ),
        );
    }
}
