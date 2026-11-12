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

use Tuxxedo\Logger\Formatter\LogFormatterInterface;
use Tuxxedo\Logger\Formatter\LogMessageInterpolator;
use Tuxxedo\Logger\Formatter\LogMessageInterpolatorInterface;
use Tuxxedo\Temporal\ClockInterface;

class SyslogLogger extends AbstractLogger
{
    private bool $opened = false;

    private readonly LogMessageInterpolatorInterface $interpolator;

    final public function __construct(
        private readonly string $ident = 'tuxxedo',
        private readonly bool $persistent = false,
        private readonly int $facility = \LOG_USER,
        private readonly int $options = \LOG_PID | \LOG_ODELAY,
        private readonly ?LogFormatterInterface $formatter = null,
        ?ClockInterface $clock = null,
    ) {
        parent::__construct(
            clock: $clock,
        );

        $this->interpolator = new LogMessageInterpolator();
    }

    public function __destruct()
    {
        if ($this->opened) {
            @\closelog();
        }
    }

    protected function writeRecord(
        LogRecordInterface $record,
    ): void {
        if (!$this->opened) {
            @\openlog(
                prefix: $this->ident,
                flags: $this->options,
                facility: $this->facility,
            );

            $this->opened = true;
        }

        @\syslog(
            priority: $this->mapLogLevelToPriority($record->level),
            message: $this->formatter !== null
                ? \rtrim($this->formatter->format($record), "\r\n")
                : $this->interpolator->interpolate($record),
        );

        if (!$this->persistent) {
            @\closelog();

            $this->opened = false;
        }
    }

    private function mapLogLevelToPriority(
        LogLevel $level,
    ): int {
        return match ($level) { // @codeCoverageIgnore
            LogLevel::EMERGENCY => \LOG_EMERG,
            LogLevel::ALERT => \LOG_ALERT,
            LogLevel::CRITICAL => \LOG_CRIT,
            LogLevel::ERROR => \LOG_ERR,
            LogLevel::WARNING => \LOG_WARNING,
            LogLevel::NOTICE => \LOG_NOTICE,
            LogLevel::INFO => \LOG_INFO,
            LogLevel::DEBUG => \LOG_DEBUG,
        };
    }
}
