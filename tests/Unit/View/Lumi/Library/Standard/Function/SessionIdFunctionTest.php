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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\Session\StubSessionAdapter;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Session\Session;
use Tuxxedo\Session\SessionStartMode;
use Tuxxedo\View\Lumi\Library\Standard\Function\SessionIdFunction;

class SessionIdFunctionTest extends TestCase
{
    private function makeContainerWithSession(
        StubSessionAdapter $adapter,
    ): Container {
        return (new Container())->singleton(
            class: new Session(
                adapter: $adapter,
            ),
        );
    }

    public function testCallReturnsSessionIdentifier(): void
    {
        $function = new SessionIdFunction(
            container: $this->makeContainerWithSession(
                adapter: new StubSessionAdapter(
                    startMode: SessionStartMode::LAZY,
                    identifier: 'sess-abc-123',
                ),
            ),
        );

        self::assertSame(
            'sess-abc-123',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReflectsIdentifierAfterRegeneration(): void
    {
        $adapter = new StubSessionAdapter(
            startMode: SessionStartMode::LAZY,
            identifier: 'sess',
        );

        $adapter->regenerateIdentifier();

        $function = new SessionIdFunction(
            container: $this->makeContainerWithSession(
                adapter: $adapter,
            ),
        );

        self::assertSame(
            'sess-1',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
