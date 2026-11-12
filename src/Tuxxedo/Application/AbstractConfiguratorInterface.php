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

use Tuxxedo\Cache\CacheManagerInterface;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Logger\LoggerInterface;
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

    /**
     * @var (\Closure(LumiConfiguratorInterface $configurator): mixed)|null
     */
    public ?\Closure $lumiCustomizer {
        get;
    }

    public ?ConnectionManagerInterface $connectionManager {
        get;
    }

    public bool $useDefaultConnectionManager {
        get;
    }

    /**
     * @var (\Closure(ConnectionManagerInterface $manager): mixed)|null
     */
    public ?\Closure $connectionManagerCustomizer {
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
     * @var (\Closure(MailConfiguratorInterface $configurator): mixed)|null
     */
    public ?\Closure $mailManagerCustomizer {
        get;
    }

    public ?CacheManagerInterface $cacheManager {
        get;
    }

    public bool $useDefaultCache {
        get;
    }

    public ?LoggerInterface $logger {
        get;
    }

    public bool $useDefaultLogger {
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
    ): static;

    /**
     * @param (\Closure(LumiConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultLumi(
        ?\Closure $customizer = null,
    ): static;

    public function withConnectionManager(
        ConnectionManagerInterface $connectionManager,
    ): static;

    /**
     * @param (\Closure(ConnectionManagerInterface $manager): mixed)|null $customizer
     */
    public function withDefaultConnectionManager(
        ?\Closure $customizer = null,
    ): static;

    public function withStorage(
        StorageInterface $storage,
    ): static;

    public function withDefaultStorage(): static;

    public function withMailManager(
        MailManagerInterface $mailManager,
    ): static;

    /**
     * @param (\Closure(MailConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultMailManager(
        ?\Closure $customizer = null,
    ): static;

    public function withCache(
        CacheManagerInterface $cacheManager,
    ): static;

    public function withDefaultCache(): static;

    public function withLogger(
        LoggerInterface $logger,
    ): static;

    public function withDefaultLogger(): static;

    public function withServiceFile(
        string $file,
    ): static;

    public function withoutServiceFiles(): static;
}
