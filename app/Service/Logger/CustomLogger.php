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

namespace App\Service\Logger;

use Tuxxedo\Container\DefaultLifecycle;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Logger\AbstractLogger;
use Tuxxedo\Logger\LogLevel;

#[DefaultLifecycle(lifecycle: Lifecycle::PERSISTENT)]
class CustomLogger extends AbstractLogger implements CustomLoggerInterface
{
    /**
     * @var LogEntry[]
     */
    public private(set) array $entries = [];

    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static {
        $timestamp = new \DateTimeImmutable();

        $this->entries[] = new LogEntry(
            date: $timestamp->format('H:i:s j/n/Y'),
            message: $this->formatter->format($message, $placeholders, $level, $timestamp),
        );

        parent::incrementByLogLevel($level);

        return $this;
    }

    public function all(): string
    {
        $list = '';

        foreach ($this->entries as $entry) {
            $list .= \sprintf(
                "[%s] %s\n",
                $entry->date,
                $entry->message,
            );
        }

        return $list;
    }

    public function count(): int
    {
        return \sizeof($this->entries);
    }
}
