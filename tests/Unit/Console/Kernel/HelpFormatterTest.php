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
use Support\Console\Stream\BufferedOutputStream;
use Tuxxedo\Console\Descriptor\ArgumentDescriptor;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Descriptor\FlagDescriptor;
use Tuxxedo\Console\Descriptor\OptionDescriptor;
use Tuxxedo\Console\Kernel\HelpFormatter;
use Tuxxedo\Console\Output\DecorationMode;
use Tuxxedo\Console\Output\StreamOutput;

class HelpFormatterTest extends TestCase
{
    private BufferedOutputStream $outputStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputStream = new BufferedOutputStream();
    }

    public function testRenderIndexReportsEmptyRegistry(): void
    {
        $this->formatter()->renderIndex(
            commands: [],
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            'Available commands:',
            $this->outputStream->bytes,
        );

        self::assertStringContainsString(
            '(none registered)',
            $this->outputStream->bytes,
        );
    }

    public function testRenderIndexAlignsPathsAndRendersDescriptions(): void
    {
        $this->formatter()->renderIndex(
            commands: [
                $this->makeDescriptor(
                    path: [
                        'a',
                    ],
                    description: 'first',
                ),
                $this->makeDescriptor(
                    path: [
                        'longer:name',
                    ],
                    description: 'second',
                ),
            ],
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString(
            '  a            first',
            $bytes,
        );

        self::assertStringContainsString(
            '  longer:name  second',
            $bytes,
        );

        self::assertStringContainsString(
            'Run "<command> --help" for details on a specific command.',
            $bytes,
        );
    }

    public function testRenderUsageIncludesOnlyPathWhenNoParameters(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
            ),
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            'Usage: demo',
            $this->outputStream->bytes,
        );
    }

    public function testRenderUsageIncludesArgumentsFlagsAndOptions(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'name',
                        position: 0,
                        description: null,
                        typeName: 'string',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: false,
                        default: null,
                        isVariadic: false,
                    ),
                    new ArgumentDescriptor(
                        name: 'age',
                        position: 1,
                        description: null,
                        typeName: 'int',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 30,
                        isVariadic: false,
                    ),
                ],
                options: [
                    new OptionDescriptor(
                        name: 'count',
                        short: 'n',
                        description: null,
                        typeName: 'int',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 1,
                        repeatable: false,
                    ),
                ],
                flags: [
                    new FlagDescriptor(
                        name: 'force',
                        short: 'f',
                        description: null,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString('[--force|-f]', $bytes);
        self::assertStringContainsString('[--count|-n=<value>]', $bytes);
        self::assertStringContainsString('<name>', $bytes);
        self::assertStringContainsString('[<age>]', $bytes);
    }

    public function testRenderPrintsDescriptionWhenPresent(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                description: 'A demo command',
            ),
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            'A demo command',
            $this->outputStream->bytes,
        );
    }

    public function testRenderSkipsDescriptionWhenEmpty(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                description: '',
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;
        $usageIndex = \strpos($bytes, 'Usage:');

        self::assertNotFalse($usageIndex);
        self::assertSame(
            $usageIndex,
            \strpos($bytes, 'Usage: demo'),
        );
    }

    public function testRenderArgumentsSectionFormatsDefaultsAndDescriptions(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'name',
                        position: 0,
                        description: 'the name',
                        typeName: 'string',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 'alice',
                        isVariadic: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString('Arguments:', $bytes);
        self::assertStringContainsString('  name  the name', $bytes);
        self::assertStringContainsString('(default: "alice")', $bytes);
    }

    public function testRenderOptionsSectionRendersShortAndDescription(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                options: [
                    new OptionDescriptor(
                        name: 'count',
                        short: 'n',
                        description: 'how many',
                        typeName: 'int',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 1,
                        repeatable: false,
                    ),
                    new OptionDescriptor(
                        name: 'plain',
                        short: null,
                        description: null,
                        typeName: 'string',
                        isBuiltin: true,
                        isNullable: true,
                        hasDefault: true,
                        default: null,
                        repeatable: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString('Options:', $bytes);
        self::assertStringContainsString('  --count, -n  how many', $bytes);
        self::assertStringContainsString('  --plain', $bytes);
        self::assertStringNotContainsString('--plain,', $bytes);
    }

    public function testRenderFlagsSectionRendersShortAndDescription(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                flags: [
                    new FlagDescriptor(
                        name: 'force',
                        short: 'f',
                        description: 'skip prompts',
                    ),
                    new FlagDescriptor(
                        name: 'verbose',
                        short: null,
                        description: null,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString('Flags:', $bytes);
        self::assertStringContainsString('  --force, -f  skip prompts', $bytes);
        self::assertStringContainsString('  --verbose', $bytes);
    }

    public function testRenderFormatsBooleanDefaults(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'flag',
                        position: 0,
                        description: null,
                        typeName: 'bool',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: true,
                        isVariadic: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            '(default: true)',
            $this->outputStream->bytes,
        );
    }

    public function testRenderFormatsNullDefault(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'value',
                        position: 0,
                        description: null,
                        typeName: 'string',
                        isBuiltin: true,
                        isNullable: true,
                        hasDefault: true,
                        default: null,
                        isVariadic: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            '(default: null)',
            $this->outputStream->bytes,
        );
    }

    public function testRenderFormatsNumericDefaults(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'n',
                        position: 0,
                        description: null,
                        typeName: 'int',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 42,
                        isVariadic: false,
                    ),
                    new ArgumentDescriptor(
                        name: 'ratio',
                        position: 1,
                        description: null,
                        typeName: 'float',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: 1.5,
                        isVariadic: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        $bytes = $this->outputStream->bytes;

        self::assertStringContainsString('(default: 42)', $bytes);
        self::assertStringContainsString('(default: 1.5)', $bytes);
    }

    public function testRenderFormatsComplexDefaultsAsPlaceholder(): void
    {
        $this->formatter()->render(
            descriptor: $this->makeDescriptor(
                path: [
                    'demo',
                ],
                arguments: [
                    new ArgumentDescriptor(
                        name: 'tags',
                        position: 0,
                        description: null,
                        typeName: 'array',
                        isBuiltin: true,
                        isNullable: false,
                        hasDefault: true,
                        default: [
                            'a',
                        ],
                        isVariadic: false,
                    ),
                ],
            ),
            output: $this->makeOutput(),
        );

        self::assertStringContainsString(
            '(default: (complex))',
            $this->outputStream->bytes,
        );
    }

    private function formatter(): HelpFormatter
    {
        return new HelpFormatter();
    }

    private function makeOutput(): StreamOutput
    {
        return new StreamOutput(
            stream: $this->outputStream,
            decorationMode: DecorationMode::NEVER,
        );
    }

    /**
     * @param list<string> $path
     * @param list<ArgumentDescriptor> $arguments
     * @param list<OptionDescriptor> $options
     * @param list<FlagDescriptor> $flags
     */
    private function makeDescriptor(
        array $path,
        ?string $description = null,
        array $arguments = [],
        array $options = [],
        array $flags = [],
    ): CommandDescriptor {
        return new CommandDescriptor(
            path: $path,
            description: $description,
            hasReturnValue: true,
            arguments: $arguments,
            options: $options,
            flags: $flags,
            className: self::class,
            methodName: __FUNCTION__,
        );
    }
}
