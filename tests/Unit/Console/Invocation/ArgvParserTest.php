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

namespace Unit\Console\Invocation;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Descriptor\FlagDescriptor;
use Tuxxedo\Console\Descriptor\OptionDescriptor;
use Tuxxedo\Console\Invocation\ArgvParser;

class ArgvParserTest extends TestCase
{
    public function testEmptyArgvProducesEmptyBuckets(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [],
            descriptor: $this->makeDescriptor(),
        );

        self::assertSame([], $parsed->positionals);
        self::assertSame([], $parsed->options);
        self::assertSame([], $parsed->flags);
    }

    public function testBarePositionalsAreCollectedInOrder(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                'a',
                'b',
                'c',
            ],
            descriptor: $this->makeDescriptor(),
        );

        self::assertSame(
            [
                'a',
                'b',
                'c',
            ],
            $parsed->positionals,
        );
    }

    public function testTerminatorForcesRemainingTokensToPositionals(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                'first',
                '--',
                '--not-an-option',
                '-x',
            ],
            descriptor: $this->makeDescriptor(),
        );

        self::assertSame(
            [
                'first',
                '--not-an-option',
                '-x',
            ],
            $parsed->positionals,
        );
    }

    public function testSingleDashIsTreatedAsPositional(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-',
            ],
            descriptor: $this->makeDescriptor(),
        );

        self::assertSame(
            [
                '-',
            ],
            $parsed->positionals,
        );
    }

    public function testLongOptionWithEqualsCollectsValue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '--count=3',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count'),
                ],
            ),
        );

        self::assertSame(
            [
                'count' => [
                    '3',
                ],
            ],
            $parsed->options,
        );
    }

    public function testLongOptionConsumesNextArgAsValue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '--count',
                '3',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count'),
                ],
            ),
        );

        self::assertSame(
            [
                'count' => [
                    '3',
                ],
            ],
            $parsed->options,
        );
    }

    public function testLongFlagSetsTrue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '--force',
            ],
            descriptor: $this->makeDescriptor(
                flags: [
                    $this->makeFlag(name: 'force'),
                ],
            ),
        );

        self::assertSame(
            [
                'force' => true,
            ],
            $parsed->flags,
        );
    }

    public function testLongFlagWithEqualsValueThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '--force=yes',
            ],
            descriptor: $this->makeDescriptor(
                flags: [
                    $this->makeFlag(name: 'force'),
                ],
            ),
        );
    }

    public function testLongOptionAtEndOfArgvThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '--count',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count'),
                ],
            ),
        );
    }

    public function testUnknownLongOptionThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '--unknown',
            ],
            descriptor: $this->makeDescriptor(),
        );
    }

    public function testUnknownLongOptionWithEqualsThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '--unknown=value',
            ],
            descriptor: $this->makeDescriptor(),
        );
    }

    public function testShortOptionWithSeparateValue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-n',
                '3',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count', short: 'n'),
                ],
            ),
        );

        self::assertSame(
            [
                'count' => [
                    '3',
                ],
            ],
            $parsed->options,
        );
    }

    public function testShortOptionWithAdjacentValue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-n3',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count', short: 'n'),
                ],
            ),
        );

        self::assertSame(
            [
                'count' => [
                    '3',
                ],
            ],
            $parsed->options,
        );
    }

    public function testShortFlagSetsTrue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-f',
            ],
            descriptor: $this->makeDescriptor(
                flags: [
                    $this->makeFlag(name: 'force', short: 'f'),
                ],
            ),
        );

        self::assertSame(
            [
                'force' => true,
            ],
            $parsed->flags,
        );
    }

    public function testCombinedShortFlagsAreAllSet(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-abc',
            ],
            descriptor: $this->makeDescriptor(
                flags: [
                    $this->makeFlag(name: 'alpha', short: 'a'),
                    $this->makeFlag(name: 'bravo', short: 'b'),
                    $this->makeFlag(name: 'charlie', short: 'c'),
                ],
            ),
        );

        self::assertSame(
            [
                'alpha' => true,
                'bravo' => true,
                'charlie' => true,
            ],
            $parsed->flags,
        );
    }

    public function testCombinedShortFlagsFollowedByOptionConsumesRemainderAsValue(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '-afhello',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'foo', short: 'f'),
                ],
                flags: [
                    $this->makeFlag(name: 'alpha', short: 'a'),
                ],
            ),
        );

        self::assertSame(
            [
                'alpha' => true,
            ],
            $parsed->flags,
        );
        self::assertSame(
            [
                'foo' => [
                    'hello',
                ],
            ],
            $parsed->options,
        );
    }

    public function testShortOptionAtEndOfArgvThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '-n',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count', short: 'n'),
                ],
            ),
        );
    }

    public function testUnknownShortOptionThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '-x',
            ],
            descriptor: $this->makeDescriptor(),
        );
    }

    public function testRepeatableOptionCollectsAllValues(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                '--tag=one',
                '--tag=two',
                '--tag',
                'three',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'tag', repeatable: true),
                ],
            ),
        );

        self::assertSame(
            [
                'tag' => [
                    'one',
                    'two',
                    'three',
                ],
            ],
            $parsed->options,
        );
    }

    public function testNonRepeatableOptionSpecifiedTwiceThrows(): void
    {
        $this->expectException(ConsoleException::class);

        (new ArgvParser())->parse(
            argv: [
                '--count=1',
                '--count=2',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count'),
                ],
            ),
        );
    }

    public function testMixedPositionalsOptionsAndFlagsParsed(): void
    {
        $parsed = (new ArgvParser())->parse(
            argv: [
                'src',
                '--count=3',
                '-f',
                'dst',
            ],
            descriptor: $this->makeDescriptor(
                options: [
                    $this->makeOption(name: 'count'),
                ],
                flags: [
                    $this->makeFlag(name: 'force', short: 'f'),
                ],
            ),
        );

        self::assertSame(
            [
                'src',
                'dst',
            ],
            $parsed->positionals,
        );

        self::assertSame(
            [
                'count' => [
                    '3',
                ],
            ],
            $parsed->options,
        );

        self::assertSame(
            [
                'force' => true,
            ],
            $parsed->flags,
        );
    }

    /**
     * @param list<OptionDescriptor> $options
     * @param list<FlagDescriptor> $flags
     */
    private function makeDescriptor(
        array $options = [],
        array $flags = [],
    ): CommandDescriptor {
        return new CommandDescriptor(
            path: [
                'demo',
            ],
            description: null,
            hasReturnValue: true,
            arguments: [],
            options: $options,
            flags: $flags,
            className: self::class,
            methodName: __FUNCTION__,
        );
    }

    private function makeOption(
        string $name,
        ?string $short = null,
        bool $repeatable = false,
    ): OptionDescriptor {
        return new OptionDescriptor(
            name: $name,
            short: $short,
            description: null,
            typeName: 'string',
            isBuiltin: true,
            isNullable: false,
            hasDefault: false,
            default: null,
            repeatable: $repeatable,
        );
    }

    private function makeFlag(
        string $name,
        ?string $short = null,
    ): FlagDescriptor {
        return new FlagDescriptor(
            name: $name,
            short: $short,
            description: null,
        );
    }
}
