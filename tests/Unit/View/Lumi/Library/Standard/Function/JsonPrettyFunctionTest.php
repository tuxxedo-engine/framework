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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonPrettyFunction;

class JsonPrettyFunctionTest extends TestCase
{
    public function testCallEncodesWithPrettyPrint(): void
    {
        self::assertSame(
            "{\n    \"key\": \"value\"\n}",
            (new JsonPrettyFunction())->call(
                [
                    [
                        'key' => 'value',
                    ],
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallOutputContainsNewlines(): void
    {
        $result = (new JsonPrettyFunction())->call(
            [
                [
                    'a' => 1,
                ],
            ],
            static fn () => new StubRuntimeContext(),
        );

        self::assertStringContainsString("\n", $result);
    }
}
