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

namespace Integration\Process;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Process\ProcessCommand;
use Tuxxedo\Process\ProcessRunner;

class ProcessHandleIntegrationTest extends TestCase
{
    private function runner(): ProcessRunner
    {
        return new ProcessRunner();
    }

    public function testStartReturnsRunningHandle(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    'usleep(200000);',
                ],
            ),
        );

        self::assertTrue($handle->isRunning);
        self::assertGreaterThan(0, $handle->pid);

        $handle->wait();
    }

    public function testPollReturnsNullWhileRunning(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    'usleep(300000);',
                ],
            ),
        );

        $result = $handle->poll();

        self::assertNull($result);
        self::assertTrue($handle->isRunning);

        $handle->wait();
    }

    public function testWaitReturnsFinalResult(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'done';",
                ],
            ),
        );

        $result = $handle->wait();

        self::assertSame('done', $result->stdout);
        self::assertSame(0, $result->exitCode);
        self::assertFalse($handle->isRunning);
    }

    public function testTerminateKillsHangingProcess(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    'usleep(3000000);',
                ],
                timeoutSeconds: 10,
            ),
        );

        self::assertTrue($handle->isRunning);

        $handle->terminate();

        self::assertFalse($handle->isRunning);
    }

    public function testPollAfterExitReturnsCachedResult(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'x';",
                ],
            ),
        );

        $first = $handle->wait();
        $second = $handle->poll();

        self::assertNotNull($second);
        self::assertSame($first->exitCode, $second->exitCode);
        self::assertSame($first->stdout, $second->stdout);
    }

    public function testWaitAfterExitReturnsCachedResult(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'y';",
                ],
            ),
        );

        $first = $handle->wait();
        $second = $handle->wait();

        self::assertSame($first->exitCode, $second->exitCode);
        self::assertSame($first->stdout, $second->stdout);
    }

    public function testTerminateAfterExitIsNoop(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'z';",
                ],
            ),
        );

        $handle->wait();

        self::assertFalse($handle->isRunning);

        $handle->terminate();

        self::assertFalse($handle->isRunning);
    }

    public function testNullTimeoutIsRespected(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'no-deadline';",
                ],
                timeoutSeconds: null,
            ),
        );

        $result = $handle->wait();

        self::assertSame('no-deadline', $result->stdout);
    }

    public function testStdoutAndStderrBothCaptured(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    "echo 'out-part'; fwrite(STDERR, 'err-part');",
                ],
            ),
        );

        $result = $handle->wait();

        self::assertSame('out-part', $result->stdout);
        self::assertSame('err-part', $result->stderr);
    }

    public function testWaitCompletesWhenChildClosesOutputEarly(): void
    {
        $handle = $this->runner()->start(
            new ProcessCommand(
                binary: \PHP_BINARY,
                arguments: [
                    '-r',
                    'fclose(STDOUT); fclose(STDERR); usleep(300000);',
                ],
            ),
        );

        $result = $handle->wait();

        self::assertSame(0, $result->exitCode);
        self::assertSame('', $result->stdout);
        self::assertSame('', $result->stderr);
    }
}
