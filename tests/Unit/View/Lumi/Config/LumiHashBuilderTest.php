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
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableParameter;

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

        $configurator->withLibraryClass(StubFilterClass::class);
        $configurator->withLibraryClass(StubFunctionClass::class);

        $filterMetadata = CallableDiscoverer::discoverFilters(StubFilterClass::class);
        $functionMetadata = CallableDiscoverer::discoverFunctions(StubFunctionClass::class);

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
        $configurator->withLibraryClass(StubFilterClass::class);

        self::assertSame(
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
        );
    }

    public function testHashChangesWhenFilterLibraryClassIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->withLibraryClass(StubFilterClass::class);

        self::assertNotSame(
            LumiHashBuilder::fromConfiguratorStandalone($base),
            LumiHashBuilder::fromConfiguratorStandalone($modified),
        );
    }

    public function testHashChangesWhenFunctionLibraryClassIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->withLibraryClass(StubFunctionClass::class);

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

    public function testHashChangesWhenPhpFilterIsAdded(): void
    {
        $base = $this->makeConfigurator();
        $modified = $this->makeConfigurator();

        $modified->addFilter(
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

    public function testHashChangesWhenFilterParameterIsRenamed(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertNotSame(
            LumiHashBuilder::fromConfigurator(
                configurator: $configurator,
                filterMetadata: [
                    $this->makeParameterMetadata('format'),
                ],
                functionMetadata: [],
            ),
            LumiHashBuilder::fromConfigurator(
                configurator: $configurator,
                filterMetadata: [
                    $this->makeParameterMetadata('pattern'),
                ],
                functionMetadata: [],
            ),
        );
    }

    public function testHashChangesWhenContextParameterIsRenamed(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertNotSame(
            LumiHashBuilder::fromConfigurator(
                configurator: $configurator,
                filterMetadata: [
                    $this->makeParameterMetadata(
                        parameterName: 'format',
                        contextParameterName: 'context',
                    ),
                ],
                functionMetadata: [],
            ),
            LumiHashBuilder::fromConfigurator(
                configurator: $configurator,
                filterMetadata: [
                    $this->makeParameterMetadata(
                        parameterName: 'format',
                        contextParameterName: 'ctx',
                    ),
                ],
                functionMetadata: [],
            ),
        );
    }

    private function makeParameterMetadata(
        string $parameterName,
        ?string $contextParameterName = null,
    ): CallableMetadata {
        return new CallableMetadata(
            name: 'date_local',
            className: \stdClass::class,
            methodName: 'dateLocal',
            wantsContext: $contextParameterName !== null,
            contextParameterIndex: $contextParameterName !== null
                ? 2
                : null,
            parameters: [
                new CallableParameter(
                    name: 'value',
                    position: 0,
                ),
                new CallableParameter(
                    name: $parameterName,
                    position: 1,
                    optional: true,
                ),
            ],
            contextParameterName: $contextParameterName,
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
