<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiViewRender;

class Test extends TestCase
{
    public function testLumi(): void
    {
        $container = new Container();

        $container->lazy(
            ConfigInterface::class,
            static fn (): ConfigInterface => Config::createFromDirectory(__DIR__ . '/../../app/config'),
        );

        /** @var LumiViewRender $renderer */
        $renderer = LumiConfigurator::fromConfig($container)->build();

        $source = $renderer->engine->compileString('{% set name = "World" %}<p>Hello {{ name }}</p>');

        self::assertTrue(\str_starts_with($source, '<?php'));
    }
}
