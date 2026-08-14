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

class ProcessHandle implements ProcessHandleInterface
{
    public readonly int $pid;

    public bool $isRunning {
        get {
            return $this->exitCode === null;
        }
    }

    /**
     * @var resource|null
     */
    private $process;

    /**
     * @var array{1?: resource, 2?: resource}
     */
    private array $pipes;

    private string $stdout = '';
    private string $stderr = '';
    private int $totalOutputBytes = 0;

    private ?int $exitCode = null;
    private readonly ?float $deadline;

    /**
     * @param resource $process
     * @param array{1?: resource, 2?: resource} $pipes
     */
    public function __construct(
        $process,
        array $pipes,
        private readonly ProcessCommandInterface $command,
        private readonly ?string $stdinFile = null,
    ) {
        $this->process = $process;
        $this->pipes = $pipes;

        foreach ($this->pipes as $pipe) {
            \stream_set_blocking($pipe, false);
        }

        $status = \proc_get_status($this->process);

        /** @var int $pid */
        $pid = $status['pid'];
        $this->pid = $pid;

        $this->deadline = $command->timeoutSeconds !== null
            ? \microtime(true) + $command->timeoutSeconds
            : null;
    }

    public function __destruct()
    {
        if ($this->process !== null) {
            @\proc_terminate($this->process);
            $this->finalize();
        }

        if ($this->stdinFile !== null) {
            @\unlink($this->stdinFile);
        }
    }

    public function poll(): ?ProcessResultInterface
    {
        if ($this->exitCode !== null) {
            return $this->buildResult();
        }

        if ($this->deadline !== null && \microtime(true) >= $this->deadline) {
            $this->kill();

            throw ProcessException::fromTimeout(
                seconds: $this->command->timeoutSeconds ?? 0,
            );
        }

        $this->tick(
            timeoutSeconds: 0,
            timeoutMicroseconds: 100_000,
        );

        return $this->exitCode !== null
            ? $this->buildResult()
            : null;
    }

    public function wait(): ProcessResultInterface
    {
        while ($this->exitCode === null) {
            $result = $this->poll();

            if ($result !== null) {
                return $result;
            }
        }

        return $this->buildResult();
    }

    public function terminate(): void
    {
        if ($this->exitCode !== null || $this->process === null) {
            return;
        }

        $this->kill();
    }

    /**
     * @throws ProcessException
     */
    private function tick(
        int $timeoutSeconds,
        int $timeoutMicroseconds,
    ): void {
        /** @var array<int, resource> $read */
        $read = [];

        /** @var array<int, resource> $write */
        $write = [];

        if (isset($this->pipes[1])) {
            $read[] = $this->pipes[1];
        }

        if (isset($this->pipes[2])) {
            $read[] = $this->pipes[2];
        }

        $except = null;

        if ($read !== []) {
            $ready = @\stream_select($read, $write, $except, $timeoutSeconds, $timeoutMicroseconds);

            if ($ready === false) {
                return; // @codeCoverageIgnore
            }

            $this->drainReadable($read);
        } else {
            \usleep($timeoutMicroseconds);
        }

        $this->captureExitIfSettled();
    }

    /**
     * @param array<int, resource> $streams
     *
     * @throws ProcessException
     */
    private function drainReadable(
        array $streams,
    ): void {
        foreach ($streams as $stream) {
            $data = @\fread($stream, 8192);

            if ($data === false || $data === '') {
                if (\feof($stream)) {
                    $this->closePipe($stream);
                }

                continue;
            }

            if (isset($this->pipes[1]) && $stream === $this->pipes[1]) {
                $this->stdout .= $data;
            } elseif (isset($this->pipes[2]) && $stream === $this->pipes[2]) {
                $this->stderr .= $data;
            }

            $this->totalOutputBytes += \strlen($data);

            $maxOutputBytes = $this->command->maxOutputBytes;

            if (
                $maxOutputBytes !== null &&
                $this->totalOutputBytes > $maxOutputBytes
            ) {
                $this->kill();

                throw ProcessException::fromOutputLimitExceeded(
                    bytes: $maxOutputBytes,
                );
            }
        }
    }

    private function captureExitIfSettled(): void
    {
        if ($this->process === null) {
            return; // @codeCoverageIgnore
        }

        $status = \proc_get_status($this->process);

        /** @var bool $running */
        $running = $status['running'];

        if ($running) {
            return;
        }

        /** @var int $exitCode */
        $exitCode = $status['exitcode'];
        $this->exitCode = $exitCode;

        $this->drainRemaining();
        $this->closeAllPipes();
    }

    private function drainRemaining(): void
    {
        foreach ([1, 2] as $fd) {
            if (!isset($this->pipes[$fd])) {
                continue;
            }

            // @codeCoverageIgnoreStart
            while (!\feof($this->pipes[$fd])) {
                $data = @\fread($this->pipes[$fd], 8192);

                if ($data === false || $data === '') {
                    break;
                }

                if ($fd === 1) {
                    $this->stdout .= $data;
                } else {
                    $this->stderr .= $data;
                }
            }
            // @codeCoverageIgnoreEnd
        }
    }

    private function closeAllPipes(): void
    {
        foreach ($this->pipes as $pipe) {
            @\fclose($pipe);
        }

        $this->pipes = [];
    }

    /**
     * @param resource $stream
     */
    private function closePipe(
        $stream,
    ): void {
        foreach ($this->pipes as $index => $pipe) {
            if ($pipe === $stream) {
                @\fclose($pipe);

                unset($this->pipes[$index]);

                return;
            }
        }
    }

    private function kill(): void
    {
        if ($this->process === null) {
            return; // @codeCoverageIgnore
        }

        @\proc_terminate($this->process);
        $this->finalize();
    }

    private function finalize(): void
    {
        $process = $this->process;

        if ($process === null) {
            return; // @codeCoverageIgnore
        }

        $this->closeAllPipes();

        $status = \proc_get_status($process);

        /** @var bool $running */
        $running = $status['running'];

        if ($running) {
            @\proc_terminate($process); // @codeCoverageIgnore
        }

        /** @var int $exitCode */
        $exitCode = $status['exitcode'];
        $this->exitCode ??= $exitCode;

        @\proc_close($process);

        $this->process = null;
    }

    private function buildResult(): ProcessResult
    {
        return new ProcessResult(
            exitCode: $this->exitCode ?? -1,
            stdout: $this->stdout,
            stderr: $this->stderr,
        );
    }
}
