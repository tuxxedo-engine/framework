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
use Tuxxedo\View\Lumi\Library\Standard\Function\ConfigFunction;

class ConfigFunctionTest extends TestCase
{
    /**
     * @param array<mixed> $directives
     */
    private function makeFunction(
        array $directives,
    ): ConfigFunction {
        $container = new Container();

        $container->persistent(
            class: new Kernel(
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
            ),
        );

        return new ConfigFunction(
            container: $container,
        );
    }

    public function testCallReturnsTopLevelDirective(): void
    {
        $function = $this->makeFunction(
            directives: [
                'app_name' => 'Tuxxedo',
            ],
        );

        self::assertSame(
            'Tuxxedo',
            $function->call(
                [
                    'app_name',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsNestedDirectiveThroughDottedPath(): void
    {
        $function = $this->makeFunction(
            directives: [
                'database' => [
                    'host' => 'localhost',
                ],
            ],
        );

        self::assertSame(
            'localhost',
            $function->call(
                [
                    'database.host',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallThrowsForUnknownDirective(): void
    {
        $function = $this->makeFunction(
            directives: [],
        );

        self::expectException(ConfigException::class);

        $function->call(
            [
                'missing',
            ],
            static fn () => new StubRuntimeContext(),
        );
    }
}
