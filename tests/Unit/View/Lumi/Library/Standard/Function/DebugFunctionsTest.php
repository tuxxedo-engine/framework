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
use Tuxxedo\View\Lumi\Library\Standard\Function\DebugFunctions;

class DebugFunctionsTest extends TestCase
{
    private DebugFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new DebugFunctions();
    }

    public function testDumpDumpsInteger(): void
    {
        self::assertSame('int(42)', $this->functions->dump(42));
    }

    public function testDumpDumpsString(): void
    {
        self::assertSame('string(5) "hello"', $this->functions->dump('hello'));
    }

    public function testDumpDumpsMultipleArguments(): void
    {
        self::assertSame("int(1)\nint(2)", $this->functions->dump(1, 2));
    }
}
