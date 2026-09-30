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
use Fixture\Console\Commands\ConflictingKindCommand;
use Fixture\Console\Commands\ConflictingParamCommand;
use Fixture\Console\Commands\DefaultDispatchCommand;
use Fixture\Console\Commands\DirectAttributeMiddlewareCommand;
use Fixture\Console\Commands\EmptyCommandNameCommand;
use Fixture\Console\Commands\GroupedCommand;
use Fixture\Console\Commands\InvalidReturnCommand;
use Fixture\Console\Commands\MissingParamTypeCommand;
use Fixture\Console\Commands\MissingReturnCommand;
use Fixture\Console\Commands\NonVariadicRepeatableOptionCommand;
use Fixture\Console\Commands\ParameterizedCommand;
use Fixture\Console\Commands\SimpleCommand;
use Fixture\Console\Commands\ThirdPartyRepeatableOptionCommand;
use Fixture\Console\Commands\VariadicOptionCommand;
use Fixture\Console\Commands\VoidCommand;
use Fixture\Console\Commands\WrapperMiddlewareCommand;
use Fixture\Console\Invocation\BinderMethodFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Console\Middleware\DirectAttributeMiddleware;
use Support\Console\Middleware\RecordingCommandMiddleware;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Kernel\CommandDiscoverer;
use Tuxxedo\Console\Middleware\CommandMiddlewareInterface;
use Tuxxedo\Container\Container;

class CommandDiscovererTest extends TestCase
{
    public function testDiscoveryIgnoresMethodsWithoutCommandAttribute(): void
    {
        $descriptors = $this->discoverer()->discover(BinderMethodFixtures::class);

        self::assertSame([], $descriptors);
    }

    public function testDiscoversSimpleCommandDescriptor(): void
    {
        $descriptors = $this->discoverer()->discover(SimpleCommand::class);

        self::assertCount(1, $descriptors);

        $descriptor = $descriptors[0];

        self::assertSame(
            [
                'demo:simple',
            ],
            $descriptor->path,
        );

        self::assertSame(
            'A simple demo command',
            $descriptor->description,
        );

        self::assertTrue($descriptor->hasReturnValue);
        self::assertSame(SimpleCommand::class, $descriptor->className);
        self::assertSame('run', $descriptor->methodName);
        self::assertSame([], $descriptor->middleware);
    }

    public function testVoidReturnMarksDescriptorAsHavingNoReturnValue(): void
    {
        $descriptor = $this->discoverer()->discover(VoidCommand::class)[0];

        self::assertFalse($descriptor->hasReturnValue);
    }

    public function testDefaultCommandDescriptorHasEmptyPath(): void
    {
        $descriptor = $this->discoverer()->discover(DefaultDispatchCommand::class)[0];

        self::assertSame([], $descriptor->path);
        self::assertSame(
            'Runs when no command is specified',
            $descriptor->description,
        );
    }

    public function testGroupedClassProducesOneDescriptorPerCommandMethod(): void
    {
        $descriptors = $this->discoverer()->discover(GroupedCommand::class);
        $paths = \array_map(
            static fn ($d): array => $d->path,
            $descriptors,
        );

        self::assertCount(2, $descriptors);
        self::assertContains(
            [
                'demo:group',
                'one',
            ],
            $paths,
        );

        self::assertContains(
            [
                'demo:group',
                'two',
            ],
            $paths,
        );
    }

    public function testParameterizedCommandDescriptorCapturesArgumentsOptionsAndFlags(): void
    {
        $descriptor = $this->discoverer()->discover(ParameterizedCommand::class)[0];

        self::assertCount(1, $descriptor->arguments);
        self::assertCount(2, $descriptor->options);
        self::assertCount(1, $descriptor->flags);

        self::assertSame('target', $descriptor->arguments[0]->name);
        self::assertSame(0, $descriptor->arguments[0]->position);
        self::assertSame('string', $descriptor->arguments[0]->typeName);

        self::assertSame('count', $descriptor->options[0]->name);
        self::assertSame('n', $descriptor->options[0]->short);
        self::assertFalse($descriptor->options[0]->repeatable);

        self::assertSame('tags', $descriptor->options[1]->name);
        self::assertTrue($descriptor->options[1]->repeatable);

        self::assertSame('force', $descriptor->flags[0]->name);
        self::assertSame('f', $descriptor->flags[0]->short);
    }

    public function testWrapperMiddlewareIsCollectedFromMethod(): void
    {
        $container = new Container();

        $container->singleton(new RecordingCommandMiddleware());

        $descriptor = (new CommandDiscoverer($container))->discover(WrapperMiddlewareCommand::class)[0];

        self::assertCount(1, $descriptor->middleware);

        $resolved = ($descriptor->middleware[0])();

        self::assertInstanceOf(
            RecordingCommandMiddleware::class,
            $resolved,
        );
    }

    public function testClassLevelWrapperMiddlewareIsCollected(): void
    {
        $container = new Container();
        $container->singleton(new RecordingCommandMiddleware());

        $descriptor = (new CommandDiscoverer($container))->discover(ClassLevelMiddlewareCommand::class)[0];

        self::assertCount(1, $descriptor->middleware);

        $resolved = ($descriptor->middleware[0])();

        self::assertInstanceOf(
            RecordingCommandMiddleware::class,
            $resolved,
        );
    }

    public function testClosureMiddlewareWrapperIsCollectedAndInvokedWithContainer(): void
    {
        $container = new Container();
        $recording = new RecordingCommandMiddleware();
        $container->singleton($recording);

        $descriptor = (new CommandDiscoverer(
            container: $container,
        ))->discover(ClosureMiddlewareCommand::class)[0];

        self::assertCount(1, $descriptor->middleware);

        $resolved = ($descriptor->middleware[0])();

        self::assertSame($recording, $resolved);
    }

    public function testDirectAttributeMiddlewareIsCollectedByInterfaceMatch(): void
    {
        $container = new Container();

        $container->singleton(new DirectAttributeMiddleware());

        $descriptor = (new CommandDiscoverer($container))->discover(DirectAttributeMiddlewareCommand::class)[0];

        self::assertCount(1, $descriptor->middleware);

        $resolved = ($descriptor->middleware[0])();

        self::assertInstanceOf(
            DirectAttributeMiddleware::class,
            $resolved,
        );

        self::assertInstanceOf(
            CommandMiddlewareInterface::class,
            $resolved,
        );
    }

    /**
     * @param class-string $commandClass
     */
    #[DataProvider('invalidCommandClassDataProvider')]
    public function testInvalidCommandClassThrows(
        string $commandClass,
    ): void {
        $this->expectException(ConsoleException::class);

        $this->discoverer()->discover($commandClass);
    }

    /**
     * @return \Generator<string, array{0: class-string}>
     */
    public static function invalidCommandClassDataProvider(): \Generator
    {
        yield 'conflicting Command and DefaultCommand on same method' => [
            ConflictingKindCommand::class,
        ];

        yield 'command method returns unsupported type' => [
            InvalidReturnCommand::class,
        ];

        yield 'command method has no return type declared' => [
            MissingReturnCommand::class,
        ];

        yield 'parameter has both Argument and Option attributes' => [
            ConflictingParamCommand::class,
        ];

        yield 'parameter has attribute but no type declared' => [
            MissingParamTypeCommand::class,
        ];

        yield 'command name is empty after whitespace trim' => [
            EmptyCommandNameCommand::class,
        ];

        yield 'RepeatableOption on non-variadic parameter' => [
            NonVariadicRepeatableOptionCommand::class,
        ];

        yield 'Option on variadic parameter' => [
            VariadicOptionCommand::class,
        ];
    }

    public function testThirdPartyAttributeImplementingRepeatableOptionInterfaceIsDiscovered(): void
    {
        $descriptor = $this->discoverer()->discover(ThirdPartyRepeatableOptionCommand::class)[0];

        self::assertCount(1, $descriptor->options);
        self::assertSame('entry', $descriptor->options[0]->name);
        self::assertSame('e', $descriptor->options[0]->short);
        self::assertSame('int', $descriptor->options[0]->typeName);
        self::assertTrue($descriptor->options[0]->repeatable);
    }

    private function discoverer(): CommandDiscoverer
    {
        return new CommandDiscoverer(
            container: new Container(),
        );
    }
}
