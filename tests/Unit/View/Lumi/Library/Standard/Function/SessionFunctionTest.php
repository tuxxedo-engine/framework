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
use Tuxxedo\View\Lumi\Library\Standard\Function\SessionFunction;

class SessionFunctionTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function makeContainerWithSession(
        array $data = [],
        SessionStartMode $startMode = SessionStartMode::LAZY,
    ): Container {
        return (new Container())->singleton(
            class: new Session(
                adapter: new StubSessionAdapter(
                    startMode: $startMode,
                    data: $data,
                ),
            ),
        );
    }

    public function testCallReturnsRawValueForGivenKey(): void
    {
        $function = new SessionFunction(
            container: $this->makeContainerWithSession(
                data: [
                    'user' => 'alice',
                ],
            ),
        );

        self::assertSame(
            'alice',
            $function->call(
                [
                    'user',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsNullForMissingKey(): void
    {
        $function = new SessionFunction(
            container: $this->makeContainerWithSession(),
        );

        self::assertNull(
            $function->call(
                [
                    'missing',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsNonStringValueVerbatim(): void
    {
        $function = new SessionFunction(
            container: $this->makeContainerWithSession(
                data: [
                    'count' => 42,
                ],
            ),
        );

        self::assertSame(
            42,
            $function->call(
                [
                    'count',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsArrayValueVerbatim(): void
    {
        $payload = [
            'role' => 'admin',
            'level' => 5,
        ];

        $function = new SessionFunction(
            container: $this->makeContainerWithSession(
                data: [
                    'profile' => $payload,
                ],
            ),
        );

        self::assertSame(
            $payload,
            $function->call(
                [
                    'profile',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
