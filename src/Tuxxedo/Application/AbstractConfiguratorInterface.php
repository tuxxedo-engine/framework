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

namespace Tuxxedo\Application;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Mail\MailConfiguratorInterface;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;

interface AbstractConfiguratorInterface
{
    public ?ConfigInterface $config {
        get;
    }

    public ?ContainerInterface $container {
        get;
    }

    public ?LumiConfiguratorInterface $lumiConfigurator {
        get;
    }

    public bool $useDefaultLumi {
        get;
    }

    public ?ConnectionManagerInterface $connectionManager {
        get;
    }

    public bool $useDefaultConnectionManager {
        get;
    }

    public ?StorageInterface $storage {
        get;
    }

    public bool $useDefaultStorage {
        get;
    }

    public ?MailManagerInterface $mailManager {
        get;
    }

    public bool $useDefaultMailManager {
        get;
    }

    /**
     * @var list<string>
     */
    public array $serviceFiles {
        get;
    }

    public function withLumi(
        LumiConfiguratorInterface $lumiConfigurator,
    ): self;

    /**
     * @param (\Closure(LumiConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultLumi(
        ?\Closure $customizer = null,
    ): self;

    public function withConnectionManager(
        ConnectionManagerInterface $connectionManager,
    ): self;

    /**
     * @param (\Closure(ConnectionManagerInterface $manager): mixed)|null $customizer
     */
    public function withDefaultConnectionManager(
        ?\Closure $customizer = null,
    ): self;

    public function withStorage(
        StorageInterface $storage,
    ): self;

    public function withDefaultStorage(): self;

    public function withMailManager(
        MailManagerInterface $mailManager,
    ): self;

    /**
     * @param (\Closure(MailConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultMailManager(
        ?\Closure $customizer = null,
    ): self;

    public function withServiceFile(
        string $file,
    ): self;

    public function withoutServiceFiles(): self;
}
