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
use Tuxxedo\Process\ProcessException;

class ProcessExceptionTest extends TestCase
{
    public function testFromLaunchFailureMentionsBinary(): void
    {
        $exception = ProcessException::fromLaunchFailure(
            binary: '/nonexistent/binary',
        );

        self::assertStringContainsString('/nonexistent/binary', $exception->getMessage());
    }

    public function testFromTimeoutMentionsSeconds(): void
    {
        $exception = ProcessException::fromTimeout(
            seconds: 42,
        );

        self::assertStringContainsString('42', $exception->getMessage());
        self::assertStringContainsString('timeout', \strtolower($exception->getMessage()));
    }

    public function testFromOutputLimitExceededMentionsBytes(): void
    {
        $exception = ProcessException::fromOutputLimitExceeded(
            bytes: 4096,
        );

        self::assertStringContainsString('4096', $exception->getMessage());
    }

    public function testFromWriteFailureMessageIsNonEmpty(): void
    {
        $exception = ProcessException::fromWriteFailure();

        self::assertNotSame('', $exception->getMessage());
    }
}
