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
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\Info\HttpRequestInfo;

class RequestProcessor implements LogProcessorInterface
{
    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        if (!$container->isBound(RequestInterface::class)) {
            return $record;
        }

        $request = $container->resolve(RequestInterface::class);

        return $record->withExtra(
            'http',
            new HttpRequestInfo(
                method: $request->method,
                path: $request->path,
                host: $request->host,
                clientIp: $request->ipAddress,
                route: $request->hasRoute()
                    ? $request->route->route->path
                    : null,
            ),
        );
    }
}
