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
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableKind;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;

class CallableMetadataTest extends TestCase
{
    public function testExposesAllProvidedFields(): void
    {
        $metadata = new CallableMetadata(
            name: 'date',
            kind: CallableKind::TYPED_ATTRIBUTE,
            className: \stdClass::class,
            methodName: 'call',
            wantsContext: true,
            aliases: [
                'time',
                'datetime',
            ],
        );

        self::assertSame('date', $metadata->name);
        self::assertSame(CallableKind::TYPED_ATTRIBUTE, $metadata->kind);
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

    public function testAliasesDefaultsToEmptyList(): void
    {
        $metadata = new CallableMetadata(
            name: 'date',
            kind: CallableKind::TYPED_ATTRIBUTE,
            className: \stdClass::class,
            methodName: 'call',
            wantsContext: true,
        );

        self::assertSame([], $metadata->aliases);
    }

    public function testTypedAttributeKindConstructs(): void
    {
        $metadata = new CallableMetadata(
            name: 'upper',
            kind: CallableKind::TYPED_ATTRIBUTE,
            className: \stdClass::class,
            methodName: 'upper',
            wantsContext: false,
        );

        self::assertSame(CallableKind::TYPED_ATTRIBUTE, $metadata->kind);
        self::assertFalse($metadata->wantsContext);
    }
}
