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

// @todo Consider a SyslogLogger
// @todo Consider a StreamLogger
// @todo Maybe LoggerManager like DB?
// @todo May benefit from having timestamps builtin and prefixed via options
interface LoggerInterface
{
    public int $total {
        get;
    }

    public int $totalAlerts {
        get;
    }

    public int $totalCriticals {
        get;
    }

    public int $totalDebugs {
        get;
    }

    public int $totalEmergencies {
        get;
    }

    public int $totalErrors {
        get;
    }

    public int $totalInfos {
        get;
    }

    public int $totalNotices {
        get;
    }

    public int $totalWarnings {
        get;
    }

    /**
     * @param array<string, scalar> $placeholders
     */
    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function alert(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function critical(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function debug(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function emergency(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function error(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function info(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function notice(
        string $message,
        array $placeholders = [],
    ): static;

    /**
     * @param array<string, scalar> $placeholders
     */
    public function warning(
        string $message,
        array $placeholders = [],
    ): static;

    public function getTotalByLogLevel(
        LogLevel $level,
    ): int;
}
