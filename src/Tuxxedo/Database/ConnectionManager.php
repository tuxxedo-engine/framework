<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Database;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\AlwaysPersistentInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\LazyInitializableInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Driver\DefaultDriver;

class ConnectionManager implements ConnectionManagerInterface, AlwaysPersistentInterface, LazyInitializableInterface
{
    /**
     * @var ConnectionInterface[]
     */
    public private(set) array $connections = [];

    private ConnectionInterface $defaultConnection;
    private ConnectionInterface $readConnection;
    private ConnectionInterface $writeConnection;

    final public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public static function createInstance(
        ContainerInterface $container,
    ): self {
        return static::createFromConfig(
            container: $container,
            path: 'database.manager',
        );
    }

    public static function createFromConfig(
        ContainerInterface $container,
        string $path,
    ): self {
        $manager = new static($container);
        $config = $container->resolve(ConfigInterface::class);

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

    public function registerConnectionFromConfig(
        string|ConfigInterface $configOrPath,
    ): self {
        if (\is_string($configOrPath)) {
            $prefix = $configOrPath;
            $config = $this->container->resolve(ConfigInterface::class);
        } else {
            $prefix = '';
            $config = $configOrPath;
        }

        if ($config->path($prefix . '.class') !== '') {
            /** @var class-string<ConnectionInterface> $class */
            $class = $config->getString($prefix . '.class');
        } else {
            $class = $config->getEnum($prefix . '.driver', DefaultDriver::class)->getDriverClass();
        }

        $this->registerConnection(
            connection: new $class($config->section($prefix)),
        );

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
