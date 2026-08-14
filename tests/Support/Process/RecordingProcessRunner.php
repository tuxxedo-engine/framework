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

namespace Support\Process;

use Tuxxedo\Process\ProcessCommandInterface;
use Tuxxedo\Process\ProcessException;
use Tuxxedo\Process\ProcessHandleInterface;
use Tuxxedo\Process\ProcessResult;
use Tuxxedo\Process\ProcessResultInterface;
use Tuxxedo\Process\ProcessRunnerInterface;

class RecordingProcessRunner implements ProcessRunnerInterface
{
    /**
     * @var list<ProcessCommandInterface>
     */
    public array $commands = [];

    public function __construct(
        private ProcessResultInterface $result = new ProcessResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
        ),
        private ?ProcessException $exception = null,
    ) {
    }

    public function setResult(
        ProcessResultInterface $result,
    ): void {
        $this->result = $result;
    }

    public function setException(
        ProcessException $exception,
    ): void {
        $this->exception = $exception;
    }

    public function run(
        ProcessCommandInterface $command,
    ): ProcessResultInterface {
        $this->commands[] = $command;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->result;
    }

    public function start(
        ProcessCommandInterface $command,
    ): ProcessHandleInterface {
        throw new \LogicException('RecordingProcessRunner: start() not implemented');
    }
}
