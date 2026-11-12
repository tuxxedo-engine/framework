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
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Logger\LogRecordInterface;

class EnvProcessor implements LogProcessorInterface
{
    /**
     * @param list<string> $keys
     */
    public function __construct(
        public readonly array $keys,
    ) {
    }

    public function process(
        ContainerInterface $container,
        LogRecordInterface $record,
    ): LogRecordInterface {
        $env = $container->resolve(EnvInterface::class);
        $values = [];

        foreach ($this->keys as $key) {
            $values[$key] = $env->has($key)
                ? $env->string($key)
                : null;
        }

        return $record->withExtra('env', $values);
    }
}
