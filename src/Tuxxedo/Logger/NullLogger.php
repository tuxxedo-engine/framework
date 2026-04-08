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

class NullLogger extends AbstractLogger
{
    public function log(
        string $message,
        array $placeholders = [],
        LogLevel $level = LogLevel::ERROR,
    ): static {
        parent::incrementByLogLevel($level);

        return $this;
    }
}
