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

use App\Service\Logger\FailingProcessor;
use Tuxxedo\Logger\Config\GroupLoggerConfig;
use Tuxxedo\Logger\Config\LoggerConfigInterface;
use Tuxxedo\Logger\Config\StreamLoggerConfig;
use Tuxxedo\Logger\Formatter\LogFormat;
use Tuxxedo\Logger\Processor\AppProcessor;
use Tuxxedo\Logger\Processor\EngineProcessor;
use Tuxxedo\Logger\Processor\EnvProcessor;
use Tuxxedo\Logger\Processor\HostProcessor;
use Tuxxedo\Logger\Processor\MemoryProcessor;
use Tuxxedo\Logger\Processor\PhpProcessor;
use Tuxxedo\Logger\Processor\RequestProcessor;

return static fn (): LoggerConfigInterface => new GroupLoggerConfig(
    loggers: [
        new StreamLoggerConfig(
            file: __DIR__ . '/../file.log',
        ),
        new StreamLoggerConfig(
            file: __DIR__ . '/../file.json.log',
            format: LogFormat::JSON,
            processors: [
                RequestProcessor::class,
                MemoryProcessor::class,
                FailingProcessor::class,
            ],
        ),
    ],
    processors: [
        AppProcessor::class,
        EngineProcessor::class,
        new EnvProcessor(
            keys: [
                'APP_NAME',
                'APP_ENVIRONMENT',
                'DEPLOY_SHA',
            ],
        ),
        HostProcessor::class,
        PhpProcessor::class,
    ],
);
