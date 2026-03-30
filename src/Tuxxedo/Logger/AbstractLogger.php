<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Logger;

abstract class AbstractLogger implements LoggerInterface
{
    public protected(set) int $total = 0;

    public protected(set) int $totalAlerts = 0;
    public protected(set) int $totalCriticals = 0;
    public protected(set) int $totalDebugs = 0;
    public protected(set) int $totalEmergencies = 0;
    public protected(set) int $totalErrors = 0;
    public protected(set) int $totalInfos = 0;
    public protected(set) int $totalNotices = 0;
    public protected(set) int $totalWarnings = 0;

    protected function incrementByLogLevel(
        LogLevel $level,
    ): void {
        $this->total++;

        match ($level) {
            LogLevel::ALERT => $this->totalAlerts++,
            LogLevel::CRITICAL => $this->totalCriticals++,
            LogLevel::DEBUG => $this->totalDebugs++,
            LogLevel::EMERGENCY => $this->totalEmergencies++,
            LogLevel::ERROR => $this->totalErrors++,
            LogLevel::INFO => $this->totalInfos++,
            LogLevel::NOTICE => $this->totalNotices++,
            LogLevel::WARNING => $this->totalWarnings++,
        };
    }

    /**
     * @param array<string, scalar> $placeholders
     */
    protected function interpolate(
        string $message,
        array $placeholders = [],
    ): string {
        $replacements = [];
        $values = [];

        foreach ($placeholders as $placeholder => $value) {
            $replacements[] = '{' . $placeholder . '}';
            $values[] = \strval($value);
        }

        return \str_replace($replacements, $values, $message);
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
            LogLevel::ALERT => $this->totalAlerts,
            LogLevel::CRITICAL => $this->totalCriticals,
            LogLevel::DEBUG => $this->totalDebugs,
            LogLevel::EMERGENCY => $this->totalEmergencies,
            LogLevel::ERROR => $this->totalErrors,
            LogLevel::INFO => $this->totalInfos,
            LogLevel::NOTICE => $this->totalNotices,
            LogLevel::WARNING => $this->totalWarnings,
        };
    }
}
