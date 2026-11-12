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

namespace Tuxxedo\Logger;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: NullLogger::class, lifecycle: Lifecycle::SINGLETON)]
interface LoggerInterface
{
    public int $total {
        get;
    }

    public int $totalAlert {
        get;
    }

    public int $totalCritical {
        get;
    }

    public int $totalDebug {
        get;
    }

    public int $totalEmergency {
        get;
    }

    public int $totalError {
        get;
    }

    public int $totalInfo {
        get;
    }

    public int $totalNotice {
        get;
    }

    public int $totalWarning {
        get;
    }

    public LogFailureMode $processorFailure {
        get;
    }

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function log(
        string $message,
        array $context = [],
        LogLevel $level = LogLevel::ERROR,
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function alert(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function critical(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function debug(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function emergency(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function error(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function info(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function notice(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    /**
     * @param literal-string $message
     * @param array<string, mixed> $context
     */
    public function warning(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static;

    public function write(
        LogRecordInterface $record,
    ): void;

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(
        array $context,
    ): LoggerInterface;

    public function withChannel(
        string $channel,
    ): LoggerInterface;

    public function getTotalByLogLevel(
        LogLevel $level,
    ): int;
}
