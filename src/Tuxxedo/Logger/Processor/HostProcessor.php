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
use Tuxxedo\Logger\Processor\Info\HostInfo;
use Tuxxedo\Logger\Processor\Info\HostInfoInterface;
use Tuxxedo\Logger\Processor\Info\OsInfo;
use Tuxxedo\Logger\Processor\Info\ProcessInfo;
use Tuxxedo\Logger\Processor\Info\ProcessInfoInterface;

class HostProcessor implements LogProcessorInterface
{
    private readonly HostInfoInterface $host;
    private readonly ProcessInfoInterface $process;

    public function __construct()
    {
        $name = \gethostname();
        $pid = \getmypid();

        $this->host = new HostInfo(
            name: $name !== false ? $name : null,
            os: new OsInfo(
                family: \PHP_OS_FAMILY,
                name: \php_uname('s'),
                release: \php_uname('r'),
                version: \php_uname('v'),
            ),
            arch: \php_uname('m'),
        );

        $this->process = new ProcessInfo(
            pid: $pid !== false ? $pid : null,
        );
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        return $record
            ->withExtra('host', $this->host)
            ->withExtra('process', $this->process);
    }
}
