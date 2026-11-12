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
use Tuxxedo\Logger\Processor\Info\EngineInfo;
use Tuxxedo\Logger\Processor\Info\EngineInfoInterface;
use Tuxxedo\Version;

class EngineProcessor implements LogProcessorInterface
{
    private readonly EngineInfoInterface $info;

    public function __construct()
    {
        $this->info = new EngineInfo(
            version: Version::SIMPLE,
            codename: Version::CODENAME,
            preview: Version::PREVIEW,
        );
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        return $record->withExtra('engine', $this->info);
    }
}
