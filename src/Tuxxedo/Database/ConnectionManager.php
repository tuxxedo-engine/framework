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

namespace Tuxxedo\Database;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Database\Config\ConnectionManagerConfigInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): ConnectionManagerInterface {
        return ConnectionManager::createFromConfig(
            container: $container,
            config: $container->resolve(ConnectionManagerConfigInterface::class),
        );
    },
)]
class ConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var ConnectionInterface[]
     */
    public private(set) array $connections = [];

    /**
     * @var array<string, ConnectionInterface>
     */
    private array $connectionsByName = [];

    private ConnectionInterface $defaultConnection;
    private ConnectionInterface $readConnection;
    private ConnectionInterface $writeConnection;

    final public function __construct()
    {
    }

    public static function createFromConfig(
        ContainerInterface $container,
        ConnectionManagerConfigInterface $config,
    ): self {
        $manager = new static();

        foreach ($config->connections as $connectionConfig) {
            $manager->registerConnection(
                connection: ($connectionConfig->driverClass)::create($container, $connectionConfig),
            );
        }

        return $manager;
    }

    private function updateDefaults(
        ConnectionInterface $connection,
    ): void {
        if ($connection->role === ConnectionRole::DEFAULT) {
            $this->defaultConnection = $connection;
        } elseif ($connection->role === ConnectionRole::DEFAULT_READ) {
            $this->readConnection = $connection;
        } elseif ($connection->role === ConnectionRole::DEFAULT_WRITE) {
            $this->writeConnection = $connection;
        }
    }

    public function registerConnection(
        ConnectionInterface $connection,
    ): self {
        $this->connections[] = $connection;
        $this->connectionsByName[$connection->name] = $connection;

        if ($connection->role !== ConnectionRole::NONE) {
            $this->updateDefaults($connection);
        }

        return $this;
    }

    public function getDefaultConnection(): ConnectionInterface
    {
        return $this->defaultConnection ?? throw DatabaseException::fromNoDefaultConnectionAvailable();
    }

    public function getReadConnection(): ConnectionInterface
    {
        return $this->readConnection ?? throw DatabaseException::fromNoReadConnectionAvailable();
    }

    public function getWriteConnection(): ConnectionInterface
    {
        return $this->writeConnection ?? throw DatabaseException::fromNoWriteConnectionAvailable();
    }

    public function getNamedConnection(
        string $name,
    ): ConnectionInterface {
        return $this->connectionsByName[$name] ?? throw DatabaseException::fromUnknownNamedConnection(
            name: $name,
        );
    }
}
