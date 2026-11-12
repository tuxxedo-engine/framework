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

namespace Tuxxedo\Logger\Processor;

use Tuxxedo\Application\Config\AppConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\Info\AppInfo;

class AppProcessor implements LogProcessorInterface
{
    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        $config = $container->resolve(AppConfigInterface::class);

        return $record->withExtra(
            'app',
            new AppInfo(
                name: $config->name,
                version: $config->version,
                environment: $config->environment,
            ),
        );
    }
}
