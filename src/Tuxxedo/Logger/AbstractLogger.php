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

use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\SystemClock;

abstract class AbstractLogger implements LoggerInterface
{
    public protected(set) int $total = 0;

    public protected(set) int $totalAlert = 0;
    public protected(set) int $totalCritical = 0;
    public protected(set) int $totalDebug = 0;
    public protected(set) int $totalEmergency = 0;
    public protected(set) int $totalError = 0;
    public protected(set) int $totalInfo = 0;
    public protected(set) int $totalNotice = 0;
    public protected(set) int $totalWarning = 0;

    protected readonly ClockInterface $clock;

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    protected ?string $channel = null;

    public function __construct(
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock();
    }

    abstract protected function writeRecord(
        LogRecordInterface $record,
    ): void;

    private function incrementByLogLevel(
        LogLevel $level,
    ): void {
        $this->total++;

        match ($level) { // @codeCoverageIgnore
            LogLevel::ALERT => $this->totalAlert++,
            LogLevel::CRITICAL => $this->totalCritical++,
            LogLevel::DEBUG => $this->totalDebug++,
            LogLevel::EMERGENCY => $this->totalEmergency++,
            LogLevel::ERROR => $this->totalError++,
            LogLevel::INFO => $this->totalInfo++,
            LogLevel::NOTICE => $this->totalNotice++,
            LogLevel::WARNING => $this->totalWarning++,
        };
    }

    public function log(
        string $message,
        array $context = [],
        LogLevel $level = LogLevel::ERROR,
        ?\Throwable $exception = null,
    ): static {
        $this->write(
            record: new LogRecord(
                message: $message,
                level: $level,
                timestamp: $this->clock->now(),
                context: [
                    ...$this->context,
                    ...$context,
                ],
                channel: $this->channel,
                exception: $exception,
            ),
        );

        return $this;
    }

    public function alert(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::ALERT,
            exception: $exception,
        );
    }

    public function critical(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::CRITICAL,
            exception: $exception,
        );
    }

    public function debug(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::DEBUG,
            exception: $exception,
        );
    }

    public function emergency(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::EMERGENCY,
            exception: $exception,
        );
    }

    public function error(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            exception: $exception,
        );
    }

    public function info(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::INFO,
            exception: $exception,
        );
    }

    public function notice(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::NOTICE,
            exception: $exception,
        );
    }

    public function warning(
        string $message,
        array $context = [],
        ?\Throwable $exception = null,
    ): static {
        return $this->log(
            message: $message,
            context: $context,
            level: LogLevel::WARNING,
            exception: $exception,
        );
    }

    public function write(
        LogRecordInterface $record,
    ): void {
        $this->writeRecord($record);
        $this->incrementByLogLevel($record->level);
    }

    public function withContext(
        array $context,
    ): LoggerInterface {
        return new ChildLogger(
            parent: $this,
            context: [
                ...$this->context,
                ...$context,
            ],
            channel: $this->channel,
            clock: $this->clock,
        );
    }

    public function withChannel(
        string $channel,
    ): LoggerInterface {
        return new ChildLogger(
            parent: $this,
            context: $this->context,
            channel: $channel,
            clock: $this->clock,
        );
    }

    public function getTotalByLogLevel(
        LogLevel $level,
    ): int {
        return match ($level) { // @codeCoverageIgnore
            LogLevel::ALERT => $this->totalAlert,
            LogLevel::CRITICAL => $this->totalCritical,
            LogLevel::DEBUG => $this->totalDebug,
            LogLevel::EMERGENCY => $this->totalEmergency,
            LogLevel::ERROR => $this->totalError,
            LogLevel::INFO => $this->totalInfo,
            LogLevel::NOTICE => $this->totalNotice,
            LogLevel::WARNING => $this->totalWarning,
        };
    }
}
