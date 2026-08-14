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

namespace Tuxxedo\Process;

class ProcessRunner implements ProcessRunnerInterface
{
    public function run(
        ProcessCommandInterface $command,
    ): ProcessResultInterface {
        return $this->start($command)->wait();
    }

    public function start(
        ProcessCommandInterface $command,
    ): ProcessHandleInterface {
        $stdinFile = self::materializeStdin($command->stdin);

        $descriptors = [
            0 => [
                'file',
                $stdinFile ?? self::nullDevice(),
                'r',
            ],
            1 => [
                'pipe',
                'w',
            ],
            2 => [
                'pipe',
                'w',
            ],
        ];

        $pipes = [];
        $argv = [
            $command->binary,
            ...$command->arguments,
        ];

        $process = @\proc_open(
            $argv,
            $descriptors,
            $pipes,
            $command->workingDirectory,
            $command->environment,
        );

        if (!\is_resource($process)) {
            if ($stdinFile !== null) {
                @\unlink($stdinFile);
            }

            throw ProcessException::fromLaunchFailure(
                binary: $command->binary,
            );
        }

        /** @var array{1?: resource, 2?: resource} $pipes */

        return new ProcessHandle(
            process: $process,
            pipes: $pipes,
            command: $command,
            stdinFile: $stdinFile,
        );
    }

    /**
     * @throws ProcessException
     */
    private static function materializeStdin(
        ?string $stdin,
    ): ?string {
        if ($stdin === null) {
            return null;
        }

        $path = \tempnam(\sys_get_temp_dir(), 'tux-proc-stdin-');

        if ($path === false) {
            throw ProcessException::fromWriteFailure(); // @codeCoverageIgnore
        }

        if (@\file_put_contents($path, $stdin) === false) {
            // @codeCoverageIgnoreStart
            @\unlink($path);

            throw ProcessException::fromWriteFailure();
            // @codeCoverageIgnoreEnd
        }

        return $path;
    }

    private static function nullDevice(): string
    {
        return \PHP_OS_FAMILY === 'Windows'
            ? 'NUL'
            : '/dev/null';
    }
}
