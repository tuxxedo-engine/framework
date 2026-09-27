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

use Fixture\Console\Commands\ClassLevelMiddlewareCommand;
use Fixture\Console\Commands\ClosureMiddlewareCommand;
use Fixture\Console\Commands\DirectAttributeMiddlewareCommand;
use Fixture\Console\Commands\SimpleCommand;
use Fixture\Console\Commands\TaggingDescriptorMiddlewareCommand;
use Fixture\Console\Commands\VoidCommand;
use Fixture\Console\Commands\WrapperMiddlewareCommand;
use PHPUnit\Framework\TestCase;
use Support\Console\Middleware\DirectAttributeMiddleware;
use Support\Console\Middleware\MiddlewareCallSequence;
use Support\Console\Middleware\RecordingCommandMiddleware;
use Support\Console\Middleware\TaggingCommandMiddleware;
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Invocation\ArgvParser;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Kernel\CommandDiscoverer;
use Tuxxedo\Console\Kernel\CommandDispatcher;
use Tuxxedo\Console\Kernel\CommandRegistry;
use Tuxxedo\Console\Kernel\CommandRegistryInterface;
use Tuxxedo\Console\Kernel\Kernel;
use Tuxxedo\Console\Middleware\CommandInvocationInterface;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Console\Output\ConsoleOutput;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\OutputInterface;
use Tuxxedo\Console\Output\StreamOutput;
use Tuxxedo\Container\Container;

class KernelPipelineTest extends TestCase
{
    private BufferedOutputStream $stdout;
    private BufferedOutputStream $stderr;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stdout = new BufferedOutputStream();
        $this->stderr = new BufferedOutputStream();
        $this->container = new Container();
    }

    public function testVoidReturningCommandYieldsSuccessExitCode(): void
    {
        $kernel = $this->makeKernel(
            commandClasses: [
                VoidCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:void',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'void',
            $this->stdout->bytes,
        );
    }

    public function testTerminalDispatchNodeExecutesCommandAndReturnsItsExitCode(): void
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
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertStringContainsString(
            'simple',
            $this->stdout->bytes,
        );
    }

    public function testGlobalMiddlewareIsInvokedThenPassesThroughToCommand(): void
    {
        $recording = new RecordingCommandMiddleware();
        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
        );

        $kernel->middleware($recording);

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:simple',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(1, $recording->callCount);
        self::assertStringContainsString(
            'simple',
            $this->stdout->bytes,
        );
    }

    public function testDescriptorWrapperMiddlewareIsInvokedFromMethodAttribute(): void
    {
        $recording = new RecordingCommandMiddleware();
        $this->container->singleton($recording);

        $kernel = $this->makeKernel(
            commandClasses: [
                WrapperMiddlewareCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:wrapper-mw',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(1, $recording->callCount);
    }

    public function testDescriptorWrapperMiddlewareIsInvokedFromClassAttribute(): void
    {
        $recording = new RecordingCommandMiddleware();
        $this->container->singleton($recording);

        $kernel = $this->makeKernel(
            commandClasses: [
                ClassLevelMiddlewareCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:class-mw',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(1, $recording->callCount);
    }

    public function testDescriptorClosureMiddlewareIsInvokedThroughContainerFactory(): void
    {
        $recording = new RecordingCommandMiddleware();
        $this->container->singleton($recording);

        $kernel = $this->makeKernel(
            commandClasses: [
                ClosureMiddlewareCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:closure-mw',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(1, $recording->callCount);
    }

    public function testDirectAttributeMiddlewareIsInvokedWhenAttributeImplementsInterface(): void
    {
        $direct = new DirectAttributeMiddleware();
        $this->container->singleton($direct);

        $kernel = $this->makeKernel(
            commandClasses: [
                DirectAttributeMiddlewareCommand::class,
            ],
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:direct-mw',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(1, $direct->callCount);
    }

    public function testGlobalMiddlewareRunsBeforeDescriptorMiddleware(): void
    {
        $sequence = new MiddlewareCallSequence();

        $this->container->singleton(
            new TaggingCommandMiddleware(
                tag: 'descriptor',
                sequence: $sequence,
            ),
        );

        $kernel = $this->makeKernel(
            commandClasses: [
                TaggingDescriptorMiddlewareCommand::class,
            ],
        );

        $kernel->middleware(
            new TaggingCommandMiddleware(
                tag: 'global',
                sequence: $sequence,
            ),
        );

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:tagging',
            ],
        );

        self::assertSame(ExitCode::SUCCESS, $exitCode);
        self::assertSame(
            [
                'global',
                'descriptor',
            ],
            $sequence->tags,
        );
    }

    public function testMiddlewarePipelineHaltsWhenInnerRefusesToDelegate(): void
    {
        $halting = new class () implements CommandMiddlewareInterface {
            public function handle(
                CommandInvocationInterface $invocation,
                CommandMiddlewareInterface $next,
            ): ExitCode {
                return ExitCode::USAGE;
            }
        };

        $kernel = $this->makeKernel(
            commandClasses: [
                SimpleCommand::class,
            ],
        );

        $kernel->middleware($halting);

        $exitCode = $kernel->run(
            argv: [
                'bin/console',
                'demo:simple',
            ],
        );

        self::assertSame(ExitCode::USAGE, $exitCode);
        self::assertSame('', $this->stdout->bytes);
    }

    /**
     * @param list<class-string> $commandClasses
     */
    private function makeKernel(
        array $commandClasses,
    ): Kernel {
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

        $this->container->singletonLazy(
            class: OutputInterface::class,
            initializer: static fn (): OutputInterface => $output->stdout,
        );

        $discoverer = new CommandDiscoverer(
            container: $this->container,
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

        $this->container->singleton($registry);
        $this->container->singletonLazy(
            class: CommandRegistryInterface::class,
            initializer: static fn (): CommandRegistryInterface => $registry,
        );

        return new Kernel(
            container: $this->container,
            dispatcher: new CommandDispatcher(
                registry: $registry,
                parser: new ArgvParser(),
                binder: new ParameterBinder(
                    container: $this->container,
                ),
                container: $this->container,
            ),
            output: $output,
        );
    }
}
