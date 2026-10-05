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
use Support\Http\Kernel\StubDispatcher;
use Support\Http\Response\StubResponseEmitter;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Router\StaticRouter;
use Tuxxedo\View\Lumi\Library\Standard\Function\ConfigFunctions;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class ConfigFunctionsTest extends TestCase
{
    /**
     * @param array<mixed> $directives
     */
    private function makeFunctions(
        array $directives,
    ): ConfigFunctions {
        $container = new Container();

        $kernel = new Kernel(
            container: $container,
            config: new Config(
                directives: $directives,
            ),
            emitter: new StubResponseEmitter(),
            dispatcher: new StubDispatcher(
                result: new Response(),
            ),
            router: new StaticRouter(
                routes: [],
            ),
        );

        return new ConfigFunctions(
            kernel: $kernel,
        );
    }

    public function testConfigReturnsTopLevelDirective(): void
    {
        self::assertSame(
            'Tuxxedo',
            $this->makeFunctions(
                [
                    'app_name' => 'Tuxxedo',
                ],
            )->config('app_name'),
        );
    }

    public function testConfigReturnsNestedDirectiveThroughDottedPath(): void
    {
        self::assertSame(
            'localhost',
            $this->makeFunctions(
                [
                    'database' => [
                        'host' => 'localhost',
                    ],
                ],
            )->config('database.host'),
        );
    }

    public function testConfigThrowsForUnknownDirective(): void
    {
        self::expectException(ConfigException::class);

        $this->makeFunctions([])->config('missing');
    }

    public function testDirectiveReturnsDirectiveValue(): void
    {
        self::assertSame(
            'bar',
            $this->makeFunctions([])->directive(
                context: new StubRuntimeContext(
                    directives: [
                        'foo' => 'bar',
                    ],
                ),
                directive: 'foo',
            ),
        );
    }

    public function testDirectiveThrowsForMissingDirective(): void
    {
        self::expectException(RuntimeException::class);

        $this->makeFunctions([])->directive(
            context: new StubRuntimeContext(),
            directive: 'missing',
        );
    }

    public function testHasDirectiveReturnsTrueForKnown(): void
    {
        self::assertTrue(
            $this->makeFunctions([])->hasDirective(
                context: new StubRuntimeContext(
                    directives: [
                        'foo' => 'bar',
                    ],
                ),
                directive: 'foo',
            ),
        );
    }

    public function testHasDirectiveReturnsFalseForMissing(): void
    {
        self::assertFalse(
            $this->makeFunctions([])->hasDirective(
                context: new StubRuntimeContext(),
                directive: 'nope',
            ),
        );
    }
}
