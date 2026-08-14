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

namespace Unit\Process;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Process\ProcessResult;

class ProcessResultTest extends TestCase
{
    public function testIsSuccessTrueForZeroExitCode(): void
    {
        $result = new ProcessResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
        );

        self::assertTrue($result->isSuccess);
    }

    public function testIsSuccessFalseForNonZeroExitCode(): void
    {
        $result = new ProcessResult(
            exitCode: 1,
            stdout: '',
            stderr: 'boom',
        );

        self::assertFalse($result->isSuccess);
    }

    public function testIsSuccessFalseForNegativeExitCode(): void
    {
        $result = new ProcessResult(
            exitCode: -1,
            stdout: '',
            stderr: '',
        );

        self::assertFalse($result->isSuccess);
    }
}
