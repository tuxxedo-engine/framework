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

use Fixture\Config\SecondTestEnum;
use Fixture\Config\TestEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Container\Container;

class ConfigTest extends TestCase
{
    private const string SINGLE_FILE = __DIR__ . '/../../Fixture/Config/single.php';
    private const string CLOSURE_FILE = __DIR__ . '/../../Fixture/Config/app.php';
    private const string DIRECTORY = __DIR__ . '/../../Fixture/Config/Many/';

    public function testCreateManually(): void
    {
        $config = new Config(
            [
                'tempDirectory' => '/tmp',
            ],
        );

        self::assertSame($config->string('tempDirectory'), '/tmp');
    }

    public function testCreateFromFile(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

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
        self::assertSame($config->string('types.string'), 'foo');
        self::assertSame($config->int('types.int'), 42);
        self::assertSame($config->float('types.float'), 13.37);
        self::assertSame($config->bool('types.bool'), false);
    }

    public function testCreateFromDirectory(): void
    {
        $config = Config::createFromDirectory(new Container(), self::DIRECTORY);

        self::assertTrue($config->has('credentials.port'));
        self::assertFalse($config->has('credential.port'));
    }

    public function testStringError(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->string('types.int');
    }

    public function testIntError(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->int('types.float');
    }

    public function testFloatError(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->float('types.bool');
    }

    public function testBoolError(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        $this->expectException(ConfigException::class);
        $config->bool('types.null');
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

    public function testEnum(): void
    {
        $config = Config::createFromFile(new Container(), self::SINGLE_FILE);

        self::assertSame($config->path('enum.foo'), TestEnum::FOO);
        self::assertSame($config->enum('enum.bar', TestEnum::class), TestEnum::BAR);

        $this->expectException(ConfigException::class);
        $config->enum('enum.baz', SecondTestEnum::class);
    }

    public function testSection(): void
    {
        $config = Config::createFromDirectory(new Container(), self::DIRECTORY);
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

    public function testContainerResolvedFile(): void
    {
        $config = Config::createFromFile(new Container(), self::CLOSURE_FILE);

        self::assertSame(
            $config->string('name'),
            'KalleLoad',
        );

        self::assertSame(
            $config->string('version'),
            '2.0.0',
        );
    }
}
