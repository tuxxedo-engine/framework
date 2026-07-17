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

namespace Unit\Model\MetaData;

use Fixture\Model\User;
use PHPUnit\Framework\TestCase;
use Support\Model\CountingMetaDataAdapter;
use Tuxxedo\Model\MetaData\MetaData;

class MetaDataTest extends TestCase
{
    public function testClearCacheForcesAdapterCallOnNextLookup(): void
    {
        $adapter = new CountingMetaDataAdapter();
        $metaData = new MetaData(adapter: $adapter);

        $metaData->getModel(model: User::class);
        $metaData->getModel(model: User::class);

        self::assertSame(
            1,
            $adapter->calls,
        );

        $metaData->clearCache();

        $metaData->getModel(model: User::class);

        self::assertSame(
            2,
            $adapter->calls,
        );
    }
}
