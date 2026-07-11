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
use Tuxxedo\Security\Jwt\Claims;
use Tuxxedo\Security\Jwt\Header;
use Tuxxedo\Security\Jwt\JwtTokenAccessor;
use Tuxxedo\Security\Jwt\Token;
use Tuxxedo\Security\Jwt\TokenInterface;

class JwtTokenAccessorTest extends TestCase
{
    private function makeToken(): TokenInterface
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

    public function testCurrentReturnsNullBeforeAnythingIsSet(): void
    {
        self::assertNull(
            (new JwtTokenAccessor())->current(),
        );
    }

    public function testSetCurrentIsExposedByCurrent(): void
    {
        $accessor = new JwtTokenAccessor();
        $token = $this->makeToken();

        $accessor->setCurrent(
            token: $token,
        );

        self::assertSame(
            $token,
            $accessor->current(),
        );
    }

    public function testSetCurrentNullClearsPreviouslySetToken(): void
    {
        $accessor = new JwtTokenAccessor();

        $accessor->setCurrent(
            token: $this->makeToken(),
        );

        $accessor->setCurrent(
            token: null,
        );

        self::assertNull(
            $accessor->current(),
        );
    }

    public function testSetCurrentOverwritesPreviouslySetToken(): void
    {
        $accessor = new JwtTokenAccessor();
        $first = $this->makeToken();
        $second = $this->makeToken();

        $accessor->setCurrent(
            token: $first,
        );

        $accessor->setCurrent(
            token: $second,
        );

        self::assertSame(
            $second,
            $accessor->current(),
        );
    }
}
