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
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonFunctions;

class JsonFunctionsTest extends TestCase
{
    private JsonFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new JsonFunctions();
    }

    public function testJsonEncodesString(): void
    {
        self::assertSame('"hello"', $this->functions->json('hello'));
    }

    public function testJsonEncodesArray(): void
    {
        self::assertSame(
            '{"key":"value"}',
            $this->functions->json(
                [
                    'key' => 'value',
                ],
            ),
        );
    }

    public function testJsonPrettyEncodesArrayWithPrettyPrint(): void
    {
        self::assertSame(
            "{\n    \"key\": \"value\"\n}",
            $this->functions->jsonPretty(
                [
                    'key' => 'value',
                ],
            ),
        );
    }

    public function testJsonPrettyOutputContainsNewlines(): void
    {
        self::assertStringContainsString(
            "\n",
            $this->functions->jsonPretty(
                [
                    'a' => 1,
                ],
            ),
        );
    }
}
