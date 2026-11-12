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

namespace Tuxxedo\Logger\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Logger\LogFailureMode;
use Tuxxedo\Logger\Processor\LogProcessorInterface;

abstract class AbstractLoggerConfig implements LoggerConfigInterface
{
    /**
     * @param list<class-string<LogProcessorInterface>|LogProcessorInterface> $processors
     */
    public function __construct(
        public readonly array $processors = [],
        public readonly LogFailureMode $processorFailure = LogFailureMode::MARKER,
    ) {
    }

    /**
     * @return list<LogProcessorInterface>
     */
    protected function createProcessors(
        ContainerInterface $container,
    ): array {
        $processors = [];

        foreach ($this->processors as $processor) {
            $processors[] = $processor instanceof LogProcessorInterface
                ? $processor
                : $container->resolve($processor);
        }

        return $processors;
    }
}
