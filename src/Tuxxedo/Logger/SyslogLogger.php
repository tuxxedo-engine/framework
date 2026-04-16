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

class SyslogLogger extends AbstractLogger
{
    private bool $opened = false;

    final public function __construct(
        private readonly string $ident = 'tuxxedo',
        private readonly bool $persistent = false,
        private readonly int $facility = \LOG_USER,
        private readonly int $options = \LOG_PID | \LOG_ODELAY,
        ?LogMessageFormatterInterface $formatter = null,
    ) {
        parent::__construct(
            formatter: $formatter,
        );
    }

    public function __destruct()
    {
        if ($this->opened) {
            @\closelog();
        }
    }

    /**
     * @param array<string, scalar> $placeholders
     */
    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static {
        if (!$this->opened) {
            @\openlog(
                prefix: $this->ident,
                flags: $this->options,
                facility: $this->facility,
            );

            $this->opened = true;
        }

        @\syslog(
            priority: $this->mapLogLevelToPriority($level),
            message: $this->formatter->interpolate($message, $placeholders),
        );

        if (!$this->persistent) {
            @\closelog();

            $this->opened = false;
        }

        parent::incrementByLogLevel($level);

        return $this;
    }

    private function mapLogLevelToPriority(
        LogLevel $level,
    ): int {
        return match ($level) {
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
