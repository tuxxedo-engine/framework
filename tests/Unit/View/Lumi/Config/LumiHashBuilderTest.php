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

namespace Unit\View\Lumi\Config;

use Fixture\View\Lumi\RecordingOptimizer;
use Fixture\View\Lumi\Runtime\Introspector\StubFilterClass;
use Fixture\View\Lumi\Runtime\Introspector\StubFunctionClass;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\View\Lumi\Config\LumiHashBuilder;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableDiscoverer;

class LumiHashBuilderTest extends TestCase
{
    private function makeContainer(): Container
    {
        $container = new Container();
        $container->singleton($container);

        return $container;
    }

    private function makeConfigurator(): LumiConfigurator
    {
        $configurator = new LumiConfigurator(
            container: $this->makeContainer(),
        );

        $configurator->withoutStandardLibrary();

        return $configurator;
    }

    public function testStandaloneEntryReturnsSameHashAsPreMetadataEntry(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withFilterClass(StubFilterClass::class);
        $configurator->withFunctionClass(StubFunctionClass::class);

        $discoverer = new CallableDiscoverer();
        $filterMetadata = $discoverer->discoverFilters(StubFilterClass::class);
        $functionMetadata = $discoverer->discoverFunctions(StubFunctionClass::class);

        self::assertSame(
            LumiHashBuilder::fromConfigurator(
                configurator: $configurator,
                filterMetadata: $filterMetadata,
                functionMetadata: $functionMetadata,
            ),
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
        );
    }

    public function testHashIsDeterministicForTheSameConfigurator(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFilterClass(StubFilterClass::class);

        self::assertSame(
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
        );
    }

    public function testHashChangesWhenFilterClassIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->withFilterClass(StubFilterClass::class);

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenFunctionClassIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->withFunctionClass(StubFunctionClass::class);

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenPhpFunctionIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->addFunction(
            name: 'abs',
        );

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenFunctionPolicyFlips(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->allowAllFunctions();

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenOptimizerIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->withCustomOptimizer(new RecordingOptimizer());

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenDirectiveIsFlipped(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->disableAutoescape();

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashIsXxh128HexDigest(): void
    {
        $configurator = $this->makeConfigurator();
        $actual = LumiHashBuilder::fromConfiguratorStandalone($configurator);

        self::assertSame(32, \strlen($actual));
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $actual);
    }
}
