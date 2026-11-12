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

use Tuxxedo\Logger\Config\LoggerConfigInterface;
use Tuxxedo\Logger\Config\StreamLoggerConfig;

return static fn (): LoggerConfigInterface => new StreamLoggerConfig(
    file: __DIR__ . '/../file.log',
);
