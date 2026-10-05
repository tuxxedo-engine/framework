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

namespace Unit\View\Lumi\Runtime\Introspector;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;

class CallableMetadataTest extends TestCase
{
    public function testExposesAllProvidedFields(): void
    {
        $metadata = new CallableMetadata(
            name: 'date',
            className: \stdClass::class,
            methodName: 'call',
            wantsContext: true,
            aliases: [
                'time',
                'datetime',
            ],
        );

        self::assertSame('date', $metadata->name);
        self::assertSame(\stdClass::class, $metadata->className);
        self::assertSame('call', $metadata->methodName);
        self::assertTrue($metadata->wantsContext);
        self::assertSame(
            [
                'time',
                'datetime',
            ],
            $metadata->aliases,
        );
    }

    public function testWantsContextFalseConstructs(): void
    {
        $metadata = new CallableMetadata(
            name: 'upper',
            className: \stdClass::class,
            methodName: 'upper',
            wantsContext: false,
        );

        self::assertFalse($metadata->wantsContext);
        self::assertNull($metadata->contextParameterIndex);
    }

    public function testAliasesDefaultsToEmptyList(): void
    {
        $metadata = new CallableMetadata(
            name: 'date',
            className: \stdClass::class,
            methodName: 'call',
            wantsContext: true,
        );

        self::assertSame([], $metadata->aliases);
    }
}
