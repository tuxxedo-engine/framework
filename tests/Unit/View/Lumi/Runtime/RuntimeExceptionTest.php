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

namespace Unit\View\Lumi\Runtime;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class RuntimeExceptionTest extends TestCase
{
    public function testFromInvalidDirectiveTypeReturnsRuntimeException(): void
    {
        $exception = RuntimeException::fromInvalidDirectiveType(
            directive: 'lumi.autoescape',
            type: 'integer',
            expectedType: 'bool',
        );

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertInstanceOf(LumiException::class, $exception);
    }

    public function testFromInvalidDirectiveTypeMessageIncludesDirectiveName(): void
    {
        $exception = RuntimeException::fromInvalidDirectiveType(
            directive: 'lumi.autoescape',
            type: 'integer',
            expectedType: 'bool',
        );

        self::assertStringContainsString('lumi.autoescape', $exception->getMessage());
    }

    public function testFromInvalidDirectiveTypeMessageIncludesActualType(): void
    {
        $exception = RuntimeException::fromInvalidDirectiveType(
            directive: 'lumi.autoescape',
            type: 'integer',
            expectedType: 'bool',
        );

        self::assertStringContainsString('integer', $exception->getMessage());
    }

    public function testFromInvalidDirectiveTypeMessageIncludesExpectedType(): void
    {
        $exception = RuntimeException::fromInvalidDirectiveType(
            directive: 'lumi.autoescape',
            type: 'integer',
            expectedType: 'bool',
        );

        self::assertStringContainsString('bool', $exception->getMessage());
    }

    public function testFromInvalidDirectiveTypeMessageMatchesExpectedFormat(): void
    {
        $exception = RuntimeException::fromInvalidDirectiveType(
            directive: 'lumi.autoescape',
            type: 'integer',
            expectedType: 'bool',
        );

        self::assertSame(
            'Tuxxedo\View\Lumi\Runtime\RuntimeException: Cannot fetch directive "lumi.autoescape" as "bool" (type is: "integer")',
            $exception->getMessage(),
        );
    }
}
