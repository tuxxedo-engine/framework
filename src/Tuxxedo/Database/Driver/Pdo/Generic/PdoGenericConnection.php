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

namespace Tuxxedo\Database\Driver\Pdo\Generic;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Builder\Dialect\DialectInterface;
use Tuxxedo\Database\Builder\Dialect\GenericDialect;
use Tuxxedo\Database\Driver\DefaultDriver;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;

class PdoGenericConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        ConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

    protected function getDriverName(): DefaultDriver
    {
        return DefaultDriver::PDO;
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new GenericDialect();
    }

    protected function getDsn(
        ConfigInterface $config,
    ): string {
        return $config->getString('dsn');
    }
}
