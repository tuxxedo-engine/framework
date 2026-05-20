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
use Tuxxedo\View\Lumi\Library\Standard\Function\HasSessionFunction;

class HasSessionFunctionTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function makeContainerWithSession(
        array $data = [],
    ): Container {
        return (new Container())->persistent(
            class: new Session(
                adapter: new StubSessionAdapter(
                    startMode: SessionStartMode::LAZY,
                    data: $data,
                ),
            ),
        );
    }

    public function testCallReturnsTrueWhenKeyExists(): void
    {
        $function = new HasSessionFunction(
            container: $this->makeContainerWithSession(
                data: [
                    'user' => 'alice',
                ],
            ),
        );

        self::assertTrue(
            $function->call(
                [
                    'user',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsFalseWhenKeyDoesNotExist(): void
    {
        $function = new HasSessionFunction(
            container: $this->makeContainerWithSession(),
        );

        self::assertFalse(
            $function->call(
                [
                    'missing',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsTrueWhenKeyExistsWithNullValue(): void
    {
        $function = new HasSessionFunction(
            container: $this->makeContainerWithSession(
                data: [
                    'nullable' => null,
                ],
            ),
        );

        self::assertTrue(
            $function->call(
                [
                    'nullable',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
