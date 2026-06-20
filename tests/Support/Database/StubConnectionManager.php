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

namespace Support\Database;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\DatabaseException;
use Tuxxedo\Database\Driver\ConnectionInterface;

class StubConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var ConnectionInterface[]
     */
    public array $connections = [];

    public function registerConnection(
        ConnectionInterface $connection,
    ): self {
        $this->connections[] = $connection;

        return $this;
    }

    public function registerConnectionFromConfig(
        string|ConfigInterface $configOrPath,
    ): self {
        return $this;
    }

    public function getDefaultConnection(): ConnectionInterface
    {
        throw DatabaseException::fromNoDefaultConnectionAvailable();
    }

    public function getReadConnection(): ConnectionInterface
    {
        throw DatabaseException::fromNoReadConnectionAvailable();
    }

    public function getWriteConnection(): ConnectionInterface
    {
        throw DatabaseException::fromNoWriteConnectionAvailable();
    }

    public function getNamedConnection(
        string $name,
    ): ConnectionInterface {
        throw DatabaseException::fromUnknownNamedConnection(
            name: $name,
        );
    }
}
