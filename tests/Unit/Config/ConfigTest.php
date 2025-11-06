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

namespace Unit\Config;

use Fixtures\Config\SecondTestEnum;
use Fixtures\Config\TestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;

class ConfigTest extends TestCase
{
    private const string SINGLE_FILE = __DIR__ . '/../../Fixtures/Config/single.php';
    private const string DIRECTORY = __DIR__ . '/../../Fixtures/Config/Many/';

    public function testCreateManually(): void
    {
        $config = new Config(
            [
                'tempDirectory' => '/tmp',
            ],
        );

        self::assertSame($config->getString('tempDirectory'), '/tmp');
    }

    public function testCreateFromFile(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        self::assertTrue($config->isString('types.string'));
        self::assertTrue($config->isInt('types.int'));
        self::assertTrue($config->isFloat('types.float'));
        self::assertTrue($config->isBool('types.bool'));
        self::assertTrue($config->isNull('types.null'));
        self::assertFalse($config->isString('type.string'));
        self::assertFalse($config->isInt('type.int'));
        self::assertFalse($config->isFloat('type.float'));
        self::assertFalse($config->isBool('type.bool'));
        self::assertFalse($config->isNull('type.null'));
        self::assertSame($config->getString('types.string'), 'foo');
        self::assertSame($config->getInt('types.int'), 42);
        self::assertSame($config->getFloat('types.float'), 13.37);
        self::assertSame($config->getBool('types.bool'), false);
    }

    public function testCreateFromDirectory(): void
    {
        $config = Config::createFromDirectory(self::DIRECTORY);

        self::assertTrue($config->has('credentials.port'));
        self::assertFalse($config->has('credential.port'));
    }

    public function testGetStringError(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->getString('types.int');
    }

    public function testGetIntError(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->getInt('types.float');
    }

    public function testGetFloatError(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->getFloat('types.bool');
    }

    public function testGetBoolError(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->getBool('types.null');
    }

    public function testPath(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        self::assertIsArray($config->path('types'));
        self::assertIsString($config->path('a'));
        self::assertSame($config->path('foo.bar.baz'), 'Hello World');

        $this->expectException(ConfigException::class);
        $config->path('');
    }

    public function testEnum(): void
    {
        $config = Config::createFromFile(self::SINGLE_FILE);

        self::assertSame($config->path('enum.foo'), TestEnum::FOO);
        self::assertSame($config->getEnum('enum.bar', TestEnum::class), TestEnum::BAR);

        $this->expectException(ConfigException::class);
        $config->getEnum('enum.baz', SecondTestEnum::class);
    }

    public function testSection(): void
    {
        $config = Config::createFromDirectory(self::DIRECTORY);
        $dirs = $config->section('other.dirs');

        self::assertTrue($dirs->has('temp'));
        self::assertTrue($dirs->has('uploads'));

        self::assertSame(
            $config->section('contact')->path('smtp.server'),
            'localhost',
        );

        $this->expectException(ConfigException::class);
        $config->section('credentials.port');
    }
}
