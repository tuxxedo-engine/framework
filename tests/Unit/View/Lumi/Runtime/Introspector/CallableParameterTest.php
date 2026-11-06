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

namespace Unit\View\Lumi\Runtime\Introspector;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameter;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class CallableParameterTest extends TestCase
{
    public function testExposesProvidedFields(): void
    {
        $parameter = new CallableParameter(
            name: 'values',
            position: 2,
            optional: true,
            variadic: true,
        );

        self::assertSame('values', $parameter->name);
        self::assertSame(2, $parameter->position);
        self::assertTrue($parameter->optional);
        self::assertTrue($parameter->variadic);
    }

    public function testOptionalAndVariadicDefaultToFalse(): void
    {
        $parameter = new CallableParameter(
            name: 'value',
            position: 0,
        );

        self::assertFalse($parameter->optional);
        self::assertFalse($parameter->variadic);
    }

    public function testListFromReflectionDescribesFunctionParameters(): void
    {
        self::assertSame(
            [
                [
                    'string',
                    0,
                    false,
                    false,
                ],
                [
                    'characters',
                    1,
                    true,
                    false,
                ],
            ],
            $this->describe(
                CallableParameter::listFromReflection(
                    new \ReflectionFunction('trim'),
                ),
            ),
        );
    }

    public function testListFromReflectionSkipsContextParameter(): void
    {
        $closure = static function (
            string $value,
            #[Context]
            RuntimeContextInterface $context,
            string ...$rest,
        ): string {
            return $value;
        };

        self::assertSame(
            [
                [
                    'value',
                    0,
                    false,
                    false,
                ],
                [
                    'rest',
                    1,
                    true,
                    true,
                ],
            ],
            $this->describe(
                CallableParameter::listFromReflection(
                    new \ReflectionFunction($closure),
                ),
            ),
        );
    }

    /**
     * @param list<CallableParameterInterface> $parameters
     * @return list<array{0: string, 1: int, 2: bool, 3: bool}>
     */
    private function describe(
        array $parameters,
    ): array {
        return \array_map(
            static fn (CallableParameterInterface $parameter): array => [
                $parameter->name,
                $parameter->position,
                $parameter->optional,
                $parameter->variadic,
            ],
            $parameters,
        );
    }
}
