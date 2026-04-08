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

    protected readonly LogMessageFormatterInterface $formatter;

    public function __construct(
        ?LogMessageFormatterInterface $formatter = null,
    ) {
        $this->formatter = $formatter ?? new LogMessageFormatter();
    }

    protected function incrementByLogLevel(
        LogLevel $level,
    ): void {
        $this->total++;

        match ($level) {
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

    public function alert(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::ALERT,
        );
    }

    public function critical(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::CRITICAL,
        );
    }

    public function debug(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::DEBUG,
        );
    }

    public function emergency(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::EMERGENCY,
        );
    }

    public function error(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
        );
    }

    public function info(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::INFO,
        );
    }

    public function notice(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::NOTICE,
        );
    }

    public function warning(
        string $message,
        array $placeholders = [],
    ): static {
        return $this->log(
            message: $message,
            placeholders: $placeholders,
            level: LogLevel::WARNING,
        );
    }

    public function getTotalByLogLevel(
        LogLevel $level,
    ): int {
        return match ($level) {
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
