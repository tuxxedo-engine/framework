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

use Fixture\Console\Invocation\BinderIntEnum;
use Fixture\Console\Invocation\BinderMethodFixtures;
use Fixture\Console\Invocation\BinderService;
use Fixture\Console\Invocation\BinderStringEnum;
use Fixture\Console\Invocation\BinderUnitEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Descriptor\CommandDescriptor;
use Tuxxedo\Console\Invocation\ParameterBinder;
use Tuxxedo\Console\Invocation\ParsedArgv;
use Tuxxedo\Container\Container;

class ParameterBinderTest extends TestCase
{
    public function testClassParamWithoutAttributeResolvesFromContainer(): void
    {
        $container = new Container();
        $service = new BinderService();
        $container->singleton($service);

        $bound = $this->binder(
            container: $container,
        )->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('classFromContainer'),
        );

        self::assertSame(
            [
                $service,
            ],
            $bound,
        );
    }

    public function testBuiltinParamWithoutAttributeThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('builtinFromContainer'),
        );
    }

    public function testUntypedParamWithoutAttributeThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('untypedFromContainer'),
        );
    }

    public function testUntypedAttributeBoundParamThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'x',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('untypedArgument'),
        );
    }

    public function testStringArgumentPassesRawValue(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'hello',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('stringArgument'),
        );

        self::assertSame(
            [
                'hello',
            ],
            $bound,
        );
    }

    public function testIntArgumentCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    '-42',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('intArgument'),
        );

        self::assertSame(
            [
                -42,
            ],
            $bound,
        );
    }

    public function testIntArgumentInvalidThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'nope',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('intArgument'),
        );
    }

    public function testFloatArgumentCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    '1.5',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('floatArgument'),
        );

        self::assertSame(
            [
                1.5,
            ],
            $bound,
        );
    }

    public function testFloatArgumentInvalidThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'nope',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('floatArgument'),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: bool}>
     */
    public static function boolCoercionDataProvider(): \Generator
    {
        yield '1 is true' => [
            '1',
            true,
        ];

        yield 'true is true' => [
            'true',
            true,
        ];

        yield 'YES is true (case-insensitive)' => [
            'YES',
            true,
        ];

        yield 'on is true' => [
            'on',
            true,
        ];

        yield '0 is false' => [
            '0',
            false,
        ];

        yield 'false is false' => [
            'false',
            false,
        ];

        yield 'no is false' => [
            'no',
            false,
        ];

        yield 'off is false' => [
            'off',
            false,
        ];
    }

    #[DataProvider('boolCoercionDataProvider')]
    public function testBoolArgumentCoerced(
        string $raw,
        bool $expected,
    ): void {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    $raw,
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('boolArgument'),
        );

        self::assertSame(
            [
                $expected,
            ],
            $bound,
        );
    }

    public function testBoolArgumentInvalidThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'maybe',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('boolArgument'),
        );
    }

    public function testUnsupportedBuiltinArgumentThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'x',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('arrayArgument'),
        );
    }

    public function testUnsupportedClassArgumentThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'x',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('unsupportedClassArgument'),
        );
    }

    public function testStringBackedEnumArgumentCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'beta',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('stringEnumArgument'),
        );

        self::assertSame(
            [
                BinderStringEnum::BETA,
            ],
            $bound,
        );
    }

    public function testStringBackedEnumInvalidValueThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'gamma',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('stringEnumArgument'),
        );
    }

    public function testIntBackedEnumArgumentCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    '2',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('intEnumArgument'),
        );

        self::assertSame(
            [
                BinderIntEnum::TWO,
            ],
            $bound,
        );
    }

    public function testIntBackedEnumWithNonNumericValueThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'two',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('intEnumArgument'),
        );
    }

    public function testIntBackedEnumWithUnknownDigitThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    '99',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('intEnumArgument'),
        );
    }

    public function testUnitEnumArgumentCoercedByCaseName(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'BETA',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('unitEnumArgument'),
        );

        self::assertSame(
            [
                BinderUnitEnum::BETA,
            ],
            $bound,
        );
    }

    public function testUnitEnumWithUnknownCaseNameThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'GAMMA',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('unitEnumArgument'),
        );
    }

    public function testMissingArgumentUsesDefault(): void
    {
        $bound = $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('defaultArgument'),
        );

        self::assertSame(
            [
                'fallback',
            ],
            $bound,
        );
    }

    public function testMissingArgumentWithoutDefaultThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('stringArgument'),
        );
    }

    public function testVariadicArgumentCollectsAllRemainingPositionals(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [
                    'a',
                    'b',
                    'c',
                ],
                options: [],
                flags: [],
            ),
            descriptor: $this->descriptor('variadicArgument'),
        );

        self::assertSame(
            [
                'a',
                'b',
                'c',
            ],
            $bound,
        );
    }

    public function testRequiredOptionCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [
                    'count' => [
                        '5',
                    ],
                ],
                flags: [],
            ),
            descriptor: $this->descriptor('requiredOption'),
        );

        self::assertSame(
            [
                5,
            ],
            $bound,
        );
    }

    public function testMissingRequiredOptionThrows(): void
    {
        $this->expectException(ConsoleException::class);

        $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('requiredOption'),
        );
    }

    public function testMissingOptionalOptionUsesDefault(): void
    {
        $bound = $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('optionalOption'),
        );

        self::assertSame(
            [
                1,
            ],
            $bound,
        );
    }

    public function testRepeatableOptionSpreadsCoercedValuesForVariadicCall(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [
                    'tag' => [
                        'one',
                        'two',
                        'three',
                    ],
                ],
                flags: [],
            ),
            descriptor: $this->descriptor('repeatableStringOption'),
        );

        self::assertSame(
            [
                'one',
                'two',
                'three',
            ],
            $bound,
        );
    }

    public function testRepeatableOptionMissingProducesNoValues(): void
    {
        $bound = $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('repeatableStringOption'),
        );

        self::assertSame([], $bound);
    }

    public function testThirdPartyRepeatableOptionAttributeIsBoundAndCoerced(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [
                    'entry' => [
                        '1',
                        '2',
                        '3',
                    ],
                ],
                flags: [],
            ),
            descriptor: $this->descriptor('thirdPartyRepeatableIntOption'),
        );

        self::assertSame(
            [
                1,
                2,
                3,
            ],
            $bound,
        );
    }

    public function testAliasedOptionUsesBindingNameInsteadOfParameterName(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [
                    'aliased' => [
                        '7',
                    ],
                ],
                flags: [],
            ),
            descriptor: $this->descriptor('aliasedOption'),
        );

        self::assertSame(
            [
                7,
            ],
            $bound,
        );
    }

    public function testFlagPresenceBindsTrue(): void
    {
        $bound = $this->binder()->bind(
            argv: new ParsedArgv(
                positionals: [],
                options: [],
                flags: [
                    'force' => true,
                ],
            ),
            descriptor: $this->descriptor('flag'),
        );

        self::assertSame(
            [
                true,
            ],
            $bound,
        );
    }

    public function testFlagAbsenceBindsFalse(): void
    {
        $bound = $this->binder()->bind(
            argv: $this->emptyArgv(),
            descriptor: $this->descriptor('flag'),
        );

        self::assertSame(
            [
                false,
            ],
            $bound,
        );
    }

    private function binder(
        ?Container $container = null,
    ): ParameterBinder {
        return new ParameterBinder(
            container: $container ?? new Container(),
        );
    }

    private function emptyArgv(): ParsedArgv
    {
        return new ParsedArgv(
            positionals: [],
            options: [],
            flags: [],
        );
    }

    private function descriptor(
        string $method,
    ): CommandDescriptor {
        return new CommandDescriptor(
            path: [
                'demo',
            ],
            description: null,
            hasReturnValue: false,
            arguments: [],
            options: [],
            flags: [],
            className: BinderMethodFixtures::class,
            methodName: $method,
        );
    }
}
