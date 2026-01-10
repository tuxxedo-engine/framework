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
use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Database\Driver\ConnectionInterface;

#[DefaultImplementation(class: ConnectionManager::class)]
interface ConnectionManagerInterface
{
    /**
     * @var ConnectionInterface[]
     */
    public array $connections {
        get;
    }

    public function registerConnection(
        ConnectionInterface $connection,
    ): self;

    /**
     * @throws DatabaseException
     */
    public function registerConnectionFromConfig(
        string|ConfigInterface $configOrPath,
    ): self;

    /**
     * @throws DatabaseException
     */
    public function getDefaultConnection(): ConnectionInterface;

    /**
     * @throws DatabaseException
     */
    public function getReadConnection(): ConnectionInterface;

    /**
     * @throws DatabaseException
     */
    public function getWriteConnection(): ConnectionInterface;

    /**
     * @throws DatabaseException
     */
    public function getNamedConnection(
        string $name,
    ): ConnectionInterface;
}
