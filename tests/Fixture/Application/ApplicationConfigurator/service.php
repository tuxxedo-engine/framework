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

use Fixture\Application\ApplicationConfigurator\ServiceMarker;
use Tuxxedo\Container\ContainerInterface;

return static function (ContainerInterface $container): void {
    ServiceMarker::$invocations[] = $container::class;
};
