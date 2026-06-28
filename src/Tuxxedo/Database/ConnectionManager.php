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

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): ConnectionManagerInterface {
        return ConnectionManager::createFromConfig(
            config: $container->resolve(ConfigInterface::class),
            path: 'database.manager',
        );
    },
)]
class ConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var ConnectionInterface[]
     */
    public private(set) array $connections = [];

    private ConnectionInterface $defaultConnection;
    private ConnectionInterface $readConnection;
    private ConnectionInterface $writeConnection;

    final public function __construct(
    ) {
    }

    public static function createFromConfig(
        ConfigInterface $config,
        string $path,
    ): self {
        $manager = new static();

        if (
            $config->has($path . '.connections') &&
            \is_array($config->path($path . '.connections'))
        ) {
            foreach (\array_keys($config->path($path . '.connections')) as $index) {
                $manager->registerConnectionFromConfig(
                    configOrPath: $path . '.connections.' . $index,
                );
            }
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

        if ($connection->role !== ConnectionRole::NONE) {
            $this->updateDefaults($connection);
        }

        return $this;
    }

    // @todo Replace with typed-config dispatch
    public function registerConnectionFromConfig(
        ConfigInterface|string $configOrPath,
    ): self {
        throw DatabaseException::fromConfigDispatchNotYetImplemented();
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
        foreach ($this->connections as $connection) {
            if ($connection->name === $name) {
                return $connection;
            }
        }

        throw DatabaseException::fromUnknownNamedConnection(
            name: $name,
        );
    }
}
