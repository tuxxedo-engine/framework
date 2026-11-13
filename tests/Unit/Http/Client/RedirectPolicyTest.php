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

namespace Unit\Http\Client;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\RedirectPolicy;

class RedirectPolicyTest extends TestCase
{
    public function testDefaults(): void
    {
        $policy = new RedirectPolicy();

        self::assertSame(5, $policy->max);
        self::assertTrue($policy->followCrossOrigin);
        self::assertFalse($policy->keepCredentialsAcrossOrigins);
    }
}
