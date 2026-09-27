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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Kernel\CommandRegistry;

class CommandRegistryTest extends TestCase
{
    public function testEmptyRegistryHasNoCommandsAndNoDefault(): void
    {
        $registry = new CommandRegistry(
            commands: [],
        );

        self::assertSame([], $registry->commands);
        self::assertNull($registry->defaultCommand);
    }

    public function testFindReturnsMatchingDescriptor(): void
    {
        $foo = $this->makeDescriptor(
            path: [
                'foo',
            ],
        );

        $bar = $this->makeDescriptor(
            path: [
                'bar',
                'baz',
            ],
        );

        $registry = new CommandRegistry(
            commands: [
                $foo,
                $bar,
            ],
        );

        self::assertSame(
            $bar,
            $registry->find(
                path: [
                    'bar',
                    'baz',
                ],
            ),
        );
    }

    public function testFindReturnsNullOnMiss(): void
    {
        $registry = new CommandRegistry(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'foo',
                    ],
                ),
            ],
        );

        self::assertNull(
            $registry->find(
                path: [
                    'nope',
                ],
            ),
        );
    }

    public function testDefaultCommandIsSeparatedFromNamedCommands(): void
    {
        $default = $this->makeDescriptor(
            path: [],
        );

        $named = $this->makeDescriptor(
            path: [
                'foo',
            ],
        );

        $registry = new CommandRegistry(
            commands: [
                $default,
                $named,
            ],
        );

        self::assertSame($default, $registry->defaultCommand);
        self::assertSame(
            [
                $named,
            ],
            $registry->commands,
        );
    }

    public function testDuplicatePathThrows(): void
    {
        $this->expectException(ConsoleException::class);

        new CommandRegistry(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'foo',
                    ],
                ),
                $this->makeDescriptor(
                    path: [
                        'foo',
                    ],
                ),
            ],
        );
    }

    public function testMultipleDefaultCommandsThrows(): void
    {
        $this->expectException(ConsoleException::class);

        new CommandRegistry(
            commands: [
                $this->makeDescriptor(
                    path: [],
                ),
                $this->makeDescriptor(
                    path: [],
                ),
            ],
        );
    }

    /**
     * @param list<string> $path
     */
    private function makeDescriptor(
        array $path,
    ): CommandDescriptor {
        return new CommandDescriptor(
            path: $path,
            description: null,
            hasReturnValue: true,
            arguments: [],
            options: [],
            flags: [],
            className: self::class,
            methodName: __FUNCTION__,
        );
    }
}
