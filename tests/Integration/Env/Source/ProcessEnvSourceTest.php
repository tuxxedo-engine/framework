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

namespace Integration\Env\Source;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\Source\ProcessEnvSource;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ProcessEnvSourceTest extends TestCase
{
    private const string PRESENT_KEY = '__TUXXEDO_TEST_PROCESS_ENV_PRESENT';
    private const string ABSENT_KEY = '__TUXXEDO_TEST_PROCESS_ENV_ABSENT';

    public function testHasReturnsTrueForSetVariable(): void
    {
        \putenv(self::PRESENT_KEY . '=set-value');

        self::assertTrue(
            (new ProcessEnvSource())->has(
                key: self::PRESENT_KEY,
            ),
        );
    }

    public function testHasReturnsFalseForUnsetVariable(): void
    {
        self::assertFalse(
            (new ProcessEnvSource())->has(
                key: self::ABSENT_KEY,
            ),
        );
    }

    public function testGetReturnsValueForSetVariable(): void
    {
        \putenv(self::PRESENT_KEY . '=hello world');

        self::assertSame(
            'hello world',
            (new ProcessEnvSource())->get(
                key: self::PRESENT_KEY,
            ),
        );
    }

    public function testGetReturnsEmptyStringForVariableSetToEmpty(): void
    {
        \putenv(self::PRESENT_KEY . '=');

        self::assertSame(
            '',
            (new ProcessEnvSource())->get(
                key: self::PRESENT_KEY,
            ),
        );
    }

    public function testGetThrowsWhenVariableIsAbsent(): void
    {
        $this->expectException(EnvException::class);

        (new ProcessEnvSource())->get(
            key: self::ABSENT_KEY,
        );
    }
}
