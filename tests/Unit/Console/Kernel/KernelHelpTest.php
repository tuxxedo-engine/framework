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

use Fixture\Console\Commands\SimpleCommand;
use PHPUnit\Framework\TestCase;
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\Config\HelpConfig;
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
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Container\Container;

class KernelHelpTest extends TestCase
{
    private BufferedOutputStream $stdout;
    private BufferedOutputStream $stderr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stdout = new BufferedOutputStream();
        $this->stderr = new BufferedOutputStream();
    }

    public function testHelpAloneRendersIndexOfRegisteredCommands(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                '--help',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'Available commands:',
            $this->stdout->bytes,
        );

        self::assertStringContainsString(
            'demo:simple',
            $this->stdout->bytes,
        );
    }

    public function testShortHelpAliasTriggersIndex(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                '-h',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'demo:simple',
            $this->stdout->bytes,
        );
    }

    public function testEmptyRegistryIndexReportsNoCommands(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                '--help',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            '(none registered)',
            $this->stdout->bytes,
        );
    }

    public function testCommandHelpRendersUsageForRegisteredCommand(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:simple',
                '--help',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'Usage: demo:simple',
            $this->stdout->bytes,
        );

        self::assertStringNotContainsString(
            'Available commands:',
            $this->stdout->bytes,
        );
    }

    public function testCustomHelpTokenIsHonoured(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
            helpConfig: new HelpConfig(
                tokens: [
                    '/?',
                ],
            ),
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:simple',
                '/?',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'Usage: demo:simple',
            $this->stdout->bytes,
        );
    }

    public function testDefaultHelpTokensDoNotTriggerWhenCustomConfigExcludesThem(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
            helpConfig: new HelpConfig(
                tokens: [
                    '/?',
                ],
            ),
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                '--help',
            ],
        );

        self::assertNotSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringNotContainsString(
            'Available commands:',
            $this->stdout->bytes,
        );
    }

    public function testHelpConfigFallsBackToDefaultTokensWhenUnbound(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
            helpConfig: null,
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                '--help',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'demo:simple',
            $this->stdout->bytes,
        );
    }

    /**
     * @param list<class-string> $commandClasses
     */
    private function makeKernel(
        array $commandClasses,
        ?HelpConfig $helpConfig = null,
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

        $container->singletonLazy(
            class: OutputInterface::class,
            initializer: static fn (): OutputInterface => $output->stdout,
        );

        if ($helpConfig !== null) {
            $container->singleton($helpConfig);
        }

        $discoverer = new CommandDiscoverer(
            container: $container,
        );

        $descriptors = [];

        foreach ($commandClasses as $class) {
            foreach ($discoverer->discover($class) as $descriptor) {
                $descriptors[] = $descriptor;
            }
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
        );
    }
}
