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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Driver\Pdo\AbstractPdoConnection;
use Tuxxedo\Database\Driver\Pdo\Config\PdoConnectionConfigInterface;
use Tuxxedo\Database\Driver\Pdo\Generic\Config\PdoGenericConnectionConfigInterface;
use Tuxxedo\Database\Query\Dialect\DialectInterface;
use Tuxxedo\Database\Query\Dialect\GenericDialect;

class PdoGenericConnection extends AbstractPdoConnection
{
    public static function create(
        ContainerInterface $container,
        PdoGenericConnectionConfigInterface $config,
    ): self {
        return new self($container, $config);
    }

    protected function getDriverDialect(): DialectInterface
    {
        return new GenericDialect();
    }

    protected function getDsn(
        PdoConnectionConfigInterface $config,
    ): string {
        return $config->dsn;
    }
}
