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
use Tuxxedo\Security\Jwt\Algorithm;

class AlgorithmTest extends TestCase
{
    public function testIsMatchesExactCaseName(): void
    {
        self::assertTrue(
            Algorithm::HS256->is(
                identifier: 'HS256',
            ),
        );
    }

    public function testIsMatchesLowercaseInput(): void
    {
        self::assertTrue(
            Algorithm::HS256->is(
                identifier: 'hs256',
            ),
        );
    }

    public function testIsMatchesMixedCaseInput(): void
    {
        self::assertTrue(
            Algorithm::EDDSA->is(
                identifier: 'EdDSA',
            ),
        );
    }

    public function testIsReturnsFalseForUnrelatedString(): void
    {
        self::assertFalse(
            Algorithm::HS256->is(
                identifier: 'none',
            ),
        );
    }

    public function testIsReturnsFalseForEmptyString(): void
    {
        self::assertFalse(
            Algorithm::HS256->is(
                identifier: '',
            ),
        );
    }

    public function testIsDoesNotMatchOtherAlgorithmNames(): void
    {
        self::assertFalse(
            Algorithm::HS256->is(
                identifier: 'HS384',
            ),
        );
    }

    public function testIsDoesNotMatchOnFamilyPrefix(): void
    {
        self::assertFalse(
            Algorithm::HS256->is(
                identifier: 'HS',
            ),
        );
    }

    public function testIdentifierReturnsCanonicalMixedCaseForEdDsa(): void
    {
        self::assertSame(
            'EdDSA',
            Algorithm::EDDSA->identifier(),
        );
    }

    public function testIdentifierReturnsEnumNameForJoseFamilies(): void
    {
        self::assertSame(
            'HS256',
            Algorithm::HS256->identifier(),
        );

        self::assertSame(
            'RS384',
            Algorithm::RS384->identifier(),
        );

        self::assertSame(
            'ES512',
            Algorithm::ES512->identifier(),
        );
    }

    public function testIsMatchesUppercaseEdDsaForCompatibility(): void
    {
        self::assertTrue(
            Algorithm::EDDSA->is(
                identifier: 'EDDSA',
            ),
        );
    }
}
