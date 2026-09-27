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

namespace Unit\Console\Kernel\Resolver;

use Fixture\Console\Commands\ResolverInspectionCommand;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Application\Environment;
use Tuxxedo\Console\ConsoleConfigurator;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Container\Container;

class AppResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ResolverInspectionCommand::reset();
    }

    public function testAllAppResolversInjectMatchingKernelValuesIntoConstructor(): void
    {
        $container = new Container();

        $kernel = ConsoleConfigurator::create(
            container: $container,
        )
            ->withAppName('engine-demo')
            ->withAppVersion('1.2.3')
            ->withAppEnvironment(Environment::STAGING)
            ->withCommandClass(ResolverInspectionCommand::class)
            ->build();

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:resolver-inspect',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertInstanceOf(
            KernelInterface::class,
            ResolverInspectionCommand::$observedKernel,
        );

        self::assertSame(
            $container->resolve(KernelInterface::class),
            ResolverInspectionCommand::$observedKernel,
        );

        self::assertSame('engine-demo', ResolverInspectionCommand::$observedName);
        self::assertSame('1.2.3', ResolverInspectionCommand::$observedVersion);
        self::assertSame(Environment::STAGING, ResolverInspectionCommand::$observedEnvironment);
    }
}
