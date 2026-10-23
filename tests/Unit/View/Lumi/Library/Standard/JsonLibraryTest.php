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

namespace Unit\View\Lumi\Library\Standard;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\JsonLibrary;

class JsonLibraryTest extends TestCase
{
    private JsonLibrary $library;

    protected function setUp(): void
    {
        $this->library = new JsonLibrary();
    }

    public function testJsonEncodesString(): void
    {
        self::assertSame('"hello"', $this->library->json('hello'));
    }

    public function testJsonEncodesArray(): void
    {
        self::assertSame(
            '{"key":"value"}',
            $this->library->json(
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
            $this->library->jsonPretty(
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
            $this->library->jsonPretty(
                [
                    'a' => 1,
                ],
            ),
        );
    }
}
