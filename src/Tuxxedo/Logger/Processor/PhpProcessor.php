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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\Info\PhpInfo;
use Tuxxedo\Logger\Processor\Info\PhpInfoInterface;

class PhpProcessor implements LogProcessorInterface
{
    private readonly PhpInfoInterface $info;

    public function __construct()
    {
        $this->info = new PhpInfo(
            version: \PHP_VERSION,
            sapi: \PHP_SAPI,
        );
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        return $record->withExtra('php', $this->info);
    }
}
