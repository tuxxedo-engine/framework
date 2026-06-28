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

namespace Unit\Config;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Container\Container;

class ConfigTest extends TestCase
{
    private const string SINGLE_FILE = __DIR__ . '/../../Fixture/Config/single.php';
    private const string DIRECTORY = __DIR__ . '/../../Fixture/Config/Many/';

    public function testCreateFromDirectory(): void
    {
        $config = Config::createFromDirectory(new Container(), self::DIRECTORY);

        self::assertTrue($config->has('credentials.port'));
        self::assertFalse($config->has('credential.port'));
    }

    public function testPath(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        self::assertIsArray($config->path('types'));
        self::assertIsString($config->path('a'));
        self::assertSame($config->path('foo.bar.baz'), 'Hello World');

        $this->expectException(ConfigException::class);
        $config->path('');
    }
}
