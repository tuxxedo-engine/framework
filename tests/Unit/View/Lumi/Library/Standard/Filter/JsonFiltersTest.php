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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonFilters;

class JsonFiltersTest extends TestCase
{
    private JsonFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new JsonFilters();
    }

    public function testJsonEncodesArray(): void
    {
        self::assertSame(
            '{"key":"value"}',
            $this->filters->json(
                [
                    'key' => 'value',
                ],
            ),
        );
    }

    public function testJsonEncodesString(): void
    {
        self::assertSame('"hello"', $this->filters->json('hello'));
    }

    public function testJsonPrettyEncodesWithPrettyPrint(): void
    {
        self::assertSame(
            "{\n    \"key\": \"value\"\n}",
            $this->filters->jsonPretty(
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
            $this->filters->jsonPretty(
                [
                    'a' => 1,
                ],
            ),
        );
    }
}
