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
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableRegistry;

class CallableRegistryTest extends TestCase
{
    public function testEmptyRegistryHasEmptyLists(): void
    {
        $registry = new CallableRegistry();

        self::assertSame([], $registry->functions);
        self::assertSame([], $registry->filters);
    }

    public function testRegisterFunctionAppendsToFunctionsList(): void
    {
        $registry = new CallableRegistry();
        $metadata = $this->makeMetadata(
            name: 'date',
        );

        $registry->registerFunction($metadata);

        self::assertSame(
            [
                $metadata,
            ],
            $registry->functions,
        );
        self::assertSame([], $registry->filters);
    }

    public function testRegisterFilterAppendsToFiltersList(): void
    {
        $registry = new CallableRegistry();
        $metadata = $this->makeMetadata(
            name: 'upper',
        );

        $registry->registerFilter($metadata);

        self::assertSame(
            [
                $metadata,
            ],
            $registry->filters,
        );
        self::assertSame([], $registry->functions);
    }

    public function testMultipleRegistrationsPreserveInsertionOrder(): void
    {
        $registry = new CallableRegistry();
        $first = $this->makeMetadata(
            name: 'first',
        );

        $second = $this->makeMetadata(
            name: 'second',
        );

        $registry->registerFilter($first);
        $registry->registerFilter($second);

        self::assertSame(
            [
                $first,
                $second,
            ],
            $registry->filters,
        );
    }

    private function makeMetadata(
        string $name,
    ): CallableMetadata {
        return new CallableMetadata(
            name: $name,
            kind: CallableKind::TYPED_ATTRIBUTE,
            className: \stdClass::class,
            methodName: $name,
            wantsContext: false,
        );
    }
}
