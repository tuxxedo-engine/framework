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
use Tuxxedo\Process\ProcessException;
use Tuxxedo\Process\ProcessRunner;

class ProcessRunnerIntegrationTest extends TestCase
{
    /**
     * @param array<string, string>|null $environment
     */
    private function runPhp(
        string $script,
        ?string $stdin = null,
        ?int $timeoutSeconds = 30,
        ?int $maxOutputBytes = null,
        ?string $workingDirectory = null,
        ?array $environment = null,
    ): \Tuxxedo\Process\ProcessResultInterface {
        $command = new ProcessCommand(
            binary: \PHP_BINARY,
            arguments: [
                '-r',
                $script,
            ],
            stdin: $stdin,
            workingDirectory: $workingDirectory,
            environment: $environment,
            timeoutSeconds: $timeoutSeconds,
            maxOutputBytes: $maxOutputBytes,
        );

        return (new ProcessRunner())->run($command);
    }

    public function testRunCapturesStdout(): void
    {
        $result = $this->runPhp(
            script: "echo 'hello';",
        );

        self::assertSame('hello', $result->stdout);
        self::assertSame(0, $result->exitCode);
        self::assertTrue($result->isSuccess);
    }

    public function testRunCapturesNonZeroExitCode(): void
    {
        $result = $this->runPhp(
            script: 'exit(3);',
        );

        self::assertSame(3, $result->exitCode);
        self::assertFalse($result->isSuccess);
    }

    public function testRunCapturesStderr(): void
    {
        $result = $this->runPhp(
            script: "fwrite(STDERR, 'oops'); exit(1);",
        );

        self::assertSame('oops', $result->stderr);
        self::assertSame(1, $result->exitCode);
    }

    public function testRunPipesStdinToChild(): void
    {
        $result = $this->runPhp(
            script: 'echo stream_get_contents(STDIN);',
            stdin: 'piped-input',
        );

        self::assertSame('piped-input', $result->stdout);
    }

    public function testRunThrowsOnTimeout(): void
    {
        try {
            $this->runPhp(
                script: 'usleep(3000000);',
                timeoutSeconds: 1,
            );

            self::fail('Expected ProcessException');
        } catch (ProcessException $exception) {
            self::assertStringContainsString('timeout', \strtolower($exception->getMessage()));
        }
    }

    public function testRunThrowsOnLaunchFailure(): void
    {
        $command = new ProcessCommand(
            binary: '/definitely/not/a/real/binary/xyz',
        );

        $this->expectException(ProcessException::class);

        (new ProcessRunner())->run($command);
    }

    public function testRunCleansUpStdinTempFileOnLaunchFailure(): void
    {
        $command = new ProcessCommand(
            binary: '/definitely/not/a/real/binary/xyz',
            stdin: 'payload',
        );

        $this->expectException(ProcessException::class);

        (new ProcessRunner())->run($command);
    }

    public function testRunDoesNotDeadlockOnLargeStdout(): void
    {
        $result = $this->runPhp(
            script: "echo str_repeat('x', 200000);",
        );

        self::assertSame(200000, \strlen($result->stdout));
        self::assertSame(0, $result->exitCode);
    }

    public function testRunEnforcesMaxOutputBytes(): void
    {
        try {
            $this->runPhp(
                script: "echo str_repeat('x', 10000);",
                maxOutputBytes: 100,
            );

            self::fail('Expected ProcessException');
        } catch (ProcessException $exception) {
            self::assertStringContainsString('100', $exception->getMessage());
        }
    }

    public function testRunRespectsWorkingDirectory(): void
    {
        $cwd = \sys_get_temp_dir();
        $result = $this->runPhp(
            script: 'echo getcwd();',
            workingDirectory: $cwd,
        );

        self::assertSame(
            \realpath($cwd),
            \realpath($result->stdout),
        );
    }

    public function testRunPassesEnvironmentToChild(): void
    {
        $result = $this->runPhp(
            script: "echo getenv('TUXXEDO_TEST_VAR');",
            environment: [
                'TUXXEDO_TEST_VAR' => 'from-parent',
            ],
        );

        self::assertSame('from-parent', $result->stdout);
    }

    public function testRunWithoutStdinCompletesCleanly(): void
    {
        $result = $this->runPhp(
            script: "echo 'no-stdin';",
        );

        self::assertSame('no-stdin', $result->stdout);
        self::assertSame(0, $result->exitCode);
    }
}
