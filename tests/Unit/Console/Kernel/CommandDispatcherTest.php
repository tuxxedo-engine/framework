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

use Fixture\Console\Commands\DefaultDispatchCommand;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\Config\SuggestionPolicy;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Invocation\ArgvParser;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Kernel\CommandDispatcher;
use Tuxxedo\Console\Kernel\CommandRegistry;
use Tuxxedo\Container\Container;

class CommandDispatcherTest extends TestCase
{
    public function testResolveReturnsInvocationForExactMatch(): void
    {
        $descriptor = $this->makeDescriptor(
            path: [
                'demo',
            ],
        );

        $invocation = $this->makeDispatcher(
            commands: [
                $descriptor,
            ],
        )->resolve(
            argv: [
                'demo',
            ],
        );

        self::assertSame($descriptor, $invocation->descriptor);
    }

    public function testResolveThrowsWhenArgvEmptyAndNoDefault(): void
    {
        $this->expectException(ConsoleException::class);

        $this->makeDispatcher(
            commands: [],
        )->resolve(
            argv: [],
        );
    }

    public function testResolveReturnsDefaultCommandWhenArgvEmpty(): void
    {
        $default = $this->makeDescriptor(
            path: [],
        );

        $invocation = $this->makeDispatcher(
            commands: [
                $default,
            ],
        )->resolve(
            argv: [],
        );

        self::assertSame($default, $invocation->descriptor);
    }

    public function testResolveThrowsWhenNoMatchAndSuggestionAbsent(): void
    {
        $dispatcher = $this->makeDispatcher(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'known',
                    ],
                ),
            ],
            suggestionPolicy: SuggestionPolicy::disabled(),
        );

        $this->expectException(ConsoleException::class);

        $dispatcher->resolve(
            argv: [
                'utterly-different-command',
            ],
        );
    }

    public function testResolveThrowsWithSuggestionWhenClosePathExists(): void
    {
        $dispatcher = $this->makeDispatcher(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'deploy',
                    ],
                ),
            ],
        );

        try {
            $dispatcher->resolve(
                argv: [
                    'deplo',
                ],
            );

            self::fail('Expected ConsoleException');
        } catch (ConsoleException $exception) {
            self::assertStringContainsString(
                'deploy',
                $exception->getMessage(),
            );
        }
    }

    public function testFindDescriptorReturnsExactMatchWithEmptyTail(): void
    {
        $descriptor = $this->makeDescriptor(
            path: [
                'foo',
            ],
        );

        $match = $this->makeDispatcher(
            commands: [
                $descriptor,
            ],
        )->findDescriptor(
            argv: [
                'foo',
            ],
        );

        self::assertNotNull($match);
        self::assertSame($descriptor, $match['descriptor']);
        self::assertSame([], $match['tail']);
    }

    public function testFindDescriptorSplitsPrefixAndTail(): void
    {
        $descriptor = $this->makeDescriptor(
            path: [
                'foo',
                'bar',
            ],
        );

        $match = $this->makeDispatcher(
            commands: [
                $descriptor,
            ],
        )->findDescriptor(
            argv: [
                'foo',
                'bar',
                'positional',
                '--flag',
            ],
        );

        self::assertNotNull($match);
        self::assertSame($descriptor, $match['descriptor']);
        self::assertSame(
            [
                'positional',
                '--flag',
            ],
            $match['tail'],
        );
    }

    public function testFindDescriptorReturnsNullWhenNoPrefixMatches(): void
    {
        $match = $this->makeDispatcher(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'foo',
                    ],
                ),
            ],
        )->findDescriptor(
            argv: [
                'other',
            ],
        );

        self::assertNull($match);
    }

    public function testFindDescriptorReturnsNullWhenArgvEmptyAndNoDefault(): void
    {
        $match = $this->makeDispatcher(
            commands: [],
        )->findDescriptor(
            argv: [],
        );

        self::assertNull($match);
    }

    public function testFindDescriptorReturnsDefaultCommandWhenArgvEmpty(): void
    {
        $default = $this->makeDescriptor(
            path: [],
        );

        $match = $this->makeDispatcher(
            commands: [
                $default,
            ],
        )->findDescriptor(
            argv: [],
        );

        self::assertNotNull($match);
        self::assertSame($default, $match['descriptor']);
        self::assertSame([], $match['tail']);
    }

    public function testSuggestionPolicyDisabledSkipsClosestPath(): void
    {
        $dispatcher = $this->makeDispatcher(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'deploy',
                    ],
                ),
            ],
            suggestionPolicy: SuggestionPolicy::disabled(),
        );

        try {
            $dispatcher->resolve(
                argv: [
                    'deplo',
                ],
            );

            self::fail('Expected ConsoleException');
        } catch (ConsoleException $exception) {
            self::assertStringNotContainsString(
                'deploy',
                $exception->getMessage(),
            );
        }
    }

    public function testSuggestionAboveThresholdIsSuppressed(): void
    {
        $dispatcher = $this->makeDispatcher(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'deploy',
                    ],
                ),
            ],
            suggestionPolicy: new SuggestionPolicy(
                enabled: true,
                maxDistance: 1,
            ),
        );

        try {
            $dispatcher->resolve(
                argv: [
                    'zzzzzz',
                ],
            );

            self::fail('Expected ConsoleException');
        } catch (ConsoleException $exception) {
            self::assertStringNotContainsString(
                'deploy',
                $exception->getMessage(),
            );
        }
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
            className: DefaultDispatchCommand::class,
            methodName: 'run',
        );
    }

    /**
     * @param list<CommandDescriptor> $commands
     */
    private function makeDispatcher(
        array $commands,
        ?SuggestionPolicy $suggestionPolicy = null,
    ): CommandDispatcher {
        $container = new Container();

        if ($suggestionPolicy !== null) {
            $container->singleton($suggestionPolicy);
        }

        return new CommandDispatcher(
            registry: new CommandRegistry(
                commands: $commands,
            ),
            parser: new ArgvParser(),
            binder: new ParameterBinder(
                container: $container,
            ),
            container: $container,
        );
    }
}
