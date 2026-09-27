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

namespace Unit\Console\Kernel;

use Fixture\Console\Commands\DeepThrowingCommand;
use Fixture\Console\Commands\ThrowingCommand;
use PHPUnit\Framework\TestCase;
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Invocation\ArgvParser;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Kernel\CommandDiscoverer;
use Tuxxedo\Console\Kernel\CommandDispatcher;
use Tuxxedo\Console\Kernel\CommandRegistry;
use Tuxxedo\Console\Kernel\CommandRegistryInterface;
use Tuxxedo\Console\Kernel\Kernel;
use Tuxxedo\Console\Output\ConsoleOutput;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Container\Container;

class KernelExceptionTest extends TestCase
{
    private BufferedOutputStream $stdout;
    private BufferedOutputStream $stderr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stdout = new BufferedOutputStream();
        $this->stderr = new BufferedOutputStream();
    }

    public function testUnhandledExceptionRenderedWithoutPrefixWhenHeaderDisabled(): void
    {
        $kernel = $this->makeKernel();

        $kernel->run(
            argv: [
                'bin/console',
                'demo:throw',
            ],
        );

        $bytes = $this->stderr->bytes;

        self::assertStringContainsString(
            'An unhandled exception occurred',
            $bytes,
        );

        self::assertStringContainsString(
            ThrowingCommand::MESSAGE,
            $bytes,
        );

        self::assertStringNotContainsString('[', $bytes);
    }

    public function testUnhandledExceptionUsesExplicitAppNameHeaderLabel(): void
    {
        $kernel = $this->makeKernel(
            appNameHeaderEnabled: true,
            appNameHeaderLabel: 'my-cli',
        );

        $kernel->run(
            argv: [
                'bin/console',
                'demo:throw',
            ],
        );

        self::assertStringContainsString(
            '[my-cli] An unhandled exception occurred',
            $this->stderr->bytes,
        );
    }

    public function testUnhandledExceptionFallsBackToAppNameWhenLabelIsNull(): void
    {
        $kernel = $this->makeKernel(
            appName: 'auto-name',
            appNameHeaderEnabled: true,
        );

        $kernel->run(
            argv: [
                'bin/console',
                'demo:throw',
            ],
        );

        self::assertStringContainsString(
            '[auto-name] An unhandled exception occurred',
            $this->stderr->bytes,
        );
    }

    public function testHeaderIsAbsentWhenEnabledButBothAppNameAndLabelAreEmpty(): void
    {
        $kernel = $this->makeKernel(
            appNameHeaderEnabled: true,
        );

        $kernel->run(
            argv: [
                'bin/console',
                'demo:throw',
            ],
        );

        $bytes = $this->stderr->bytes;

        self::assertStringContainsString('An unhandled exception occurred', $bytes);
        self::assertStringNotContainsString('[', $bytes);
    }

    public function testConsoleExceptionSkipsUnhandledPathAndUsesHandleAsError(): void
    {
        $kernel = $this->makeKernel();

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'utterly-unknown-word',
            ],
        );

        self::assertSame(ExitCode::COMMAND_NOT_FOUND, $exitCode);
        self::assertStringNotContainsString(
            'An unhandled exception occurred',
            $this->stderr->bytes,
        );
    }

    public function testDeepPreviousChainIsTruncatedAfterMaxDepth(): void
    {
        $kernel = $this->makeKernel();

        $kernel->run(
            argv: [
                'bin/console',
                'demo:deep-throw',
            ],
        );

        self::assertStringContainsString(
            'more causes',
            $this->stderr->bytes,
        );
    }

    private function makeKernel(
        string $appName = '',
        bool $appNameHeaderEnabled = false,
        ?string $appNameHeaderLabel = null,
    ): Kernel {
        $container = new Container();

        $output = new ConsoleOutput(
            stdout: new StreamOutput(
                stream: $this->stdout,
                decorationMode: DecorationMode::NEVER,
            ),
            stderr: new StreamOutput(
                stream: $this->stderr,
                decorationMode: DecorationMode::NEVER,
            ),
        );

        $discoverer = new CommandDiscoverer(
            container: $container,
        );

        $descriptors = [];

        foreach ($discoverer->discover(ThrowingCommand::class) as $descriptor) {
            $descriptors[] = $descriptor;
        }

        foreach ($discoverer->discover(DeepThrowingCommand::class) as $descriptor) {
            $descriptors[] = $descriptor;
        }

        $registry = new CommandRegistry(
            commands: $descriptors,
        );

        $container->singleton($registry);
        $container->singletonLazy(
            class: CommandRegistryInterface::class,
            initializer: static fn (): CommandRegistryInterface => $registry,
        );

        return new Kernel(
            container: $container,
            dispatcher: new CommandDispatcher(
                registry: $registry,
                parser: new ArgvParser(),
                binder: new ParameterBinder(
                    container: $container,
                ),
                container: $container,
            ),
            output: $output,
            appName: $appName,
            appNameHeaderEnabled: $appNameHeaderEnabled,
            appNameHeaderLabel: $appNameHeaderLabel,
        );
    }
}
