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
use Tuxxedo\Http\Client\Auth\MemoryTokenStore;

class MemoryTokenStoreTest extends TestCase
{
    public function testStoresAndClears(): void
    {
        $initial = new AccessToken('a');
        $next = new AccessToken('b');
        $store = new MemoryTokenStore($initial);
        $states = [
            $store->get(),
        ];

        $store->store($next);
        $states[] = $store->get();

        $store->clear();
        $states[] = $store->get();

        self::assertSame(
            [
                $initial,
                $next,
                null,
            ],
            $states,
        );

        self::assertNull(new MemoryTokenStore()->get());
    }
}
