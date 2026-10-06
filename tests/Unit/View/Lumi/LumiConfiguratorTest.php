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

namespace Unit\View\Lumi;

use Fixture\View\Lumi\RecordingOptimizer;
use Fixture\View\Lumi\Runtime\Introspector\StubFilterClass;
use Fixture\View\Lumi\Runtime\Introspector\StubFunctionClass;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\View\Lumi\Config\LumiConfig;
use Tuxxedo\View\Lumi\Config\LumiHashBuilder;
use Tuxxedo\View\Lumi\Highlight\ColorSlot;
use Tuxxedo\View\Lumi\Highlight\Theme\LumiDark;
use Tuxxedo\View\Lumi\Highlight\Theme\ThemeInterface;
use Tuxxedo\View\Lumi\Library\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\LumiException;
use Tuxxedo\View\Lumi\LumiViewRender;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\ViewRenderInterface;

class LumiConfiguratorTest extends TestCase
{
    private function makeContainer(): Container
    {
        $container = new Container();
        $container->singleton($container);

        return $container;
    }

    private function makeConfigurator(): LumiConfigurator
    {
        return new LumiConfigurator(
            container: $this->makeContainer(),
        );
    }

    private function makeContainerWithConfig(
        string $directory = '',
        string $cacheDirectory = '',
        string $extension = '',
        bool $alwaysCompile = false,
        bool $disableErrorReporting = true,
    ): Container {
        $container = new Container();
        $config = new LumiConfig(
            directory: $directory,
            cacheDirectory: $cacheDirectory,
            extension: $extension,
            alwaysCompile: $alwaysCompile,
            disableErrorReporting: $disableErrorReporting,
        );

        $container->singleton($config);

        return $container;
    }

    public function testImplementsLumiConfiguratorInterface(): void
    {
        self::assertInstanceOf(
            LumiConfiguratorInterface::class,
            $this->makeConfigurator(),
        );
    }

    public function testConstructorSetsDefaultDirectives(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertSame(DefaultDirectives::defaults(), $configurator->defaultDirectives);
    }

    public function testConstructorSetsDefaultOptimizersIncludingSccp(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertArrayHasKey(SccpOptimizer::class, $configurator->optimizers);
    }

    public function testConstructorSetsDefaultOptimizersIncludingDce(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertArrayHasKey(DceOptimizer::class, $configurator->optimizers);
    }

    public function testConstructorDefaultOptimizersMatchCreateDefaultOptimizers(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertCount(
            \sizeof(LumiEngine::createDefaultOptimizers()),
            $configurator->optimizers,
        );
    }

    public function testConstructorDefaultsFunctionPolicyToCustomOnly(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $configurator->functionPolicy);
    }

    public function testConstructorDefaultsWithStandardLibraryToTrue(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertTrue($configurator->withStandardLibrary);
    }

    public function testConstructorDefaultsViewDisableErrorReportingToTrue(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertTrue($configurator->viewDisableErrorReporting);
    }

    public function testFromConfigSetsViewDirectoryWhenKeyPresent(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                directory: '/tmp/views',
            ),
        );

        self::assertSame('/tmp/views', $configurator->viewDirectory);
    }

    public function testFromConfigSetsCacheDirectoryWhenKeyPresent(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                cacheDirectory: '/tmp/cache',
            ),
        );

        self::assertSame('/tmp/cache', $configurator->viewCacheDirectory);
    }

    public function testFromConfigSetsViewExtensionWhenKeyPresent(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                extension: 'lumi',
            ),
        );

        self::assertSame('lumi', $configurator->viewExtension);
    }

    public function testFromConfigEnablesAlwaysCompileWhenTrue(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                alwaysCompile: true,
            ),
        );

        self::assertTrue($configurator->viewAlwaysCompile);
    }

    public function testFromConfigDisablesAlwaysCompileWhenFalse(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                alwaysCompile: false,
            ),
        );

        self::assertFalse($configurator->viewAlwaysCompile);
    }

    public function testFromConfigDisablesErrorReportingWhenTrue(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                disableErrorReporting: true,
            ),
        );

        self::assertTrue($configurator->viewDisableErrorReporting);
    }

    public function testFromConfigEnablesErrorReportingWhenFalse(): void
    {
        $configurator = LumiConfigurator::fromConfig(
            $this->makeContainerWithConfig(
                disableErrorReporting: false,
            ),
        );

        self::assertFalse($configurator->viewDisableErrorReporting);
    }

    public function testFromConfigSkipsKeysNotPresent(): void
    {
        $configurator = LumiConfigurator::fromConfig($this->makeContainerWithConfig());

        self::assertSame('', $configurator->viewDirectory);
        self::assertSame('', $configurator->viewCacheDirectory);
        self::assertSame('', $configurator->viewExtension);
        self::assertFalse($configurator->viewAlwaysCompile);
    }

    public function testFromConfigReturnsStaticInstance(): void
    {
        $configurator = LumiConfigurator::fromConfig($this->makeContainerWithConfig());

        self::assertInstanceOf(LumiConfigurator::class, $configurator);
    }

    public function testViewDirectorySetsPropertyAndReturnsFluentSelf(): void
    {
        $configurator = $this->makeConfigurator();
        $result = $configurator->viewDirectory('/var/views');

        self::assertSame('/var/views', $configurator->viewDirectory);
        self::assertSame($configurator, $result);
    }

    public function testViewExtensionSetsPropertyAndReturnsFluentSelf(): void
    {
        $configurator = $this->makeConfigurator();
        $result = $configurator->viewExtension('html');

        self::assertSame('html', $configurator->viewExtension);
        self::assertSame($configurator, $result);
    }

    public function testCacheDirectorySetsPropertyAndReturnsFluentSelf(): void
    {
        $configurator = $this->makeConfigurator();
        $result = $configurator->cacheDirectory('/var/cache');

        self::assertSame('/var/cache', $configurator->viewCacheDirectory);
        self::assertSame($configurator, $result);
    }

    public function testEnableAutoescapeSetsDefaultDirectiveTrue(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableAutoescape();

        self::assertTrue($configurator->defaultDirectives['lumi.autoescape']);
    }

    public function testDisableAutoescapeSetsDefaultDirectiveFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disableAutoescape();

        self::assertFalse($configurator->defaultDirectives['lumi.autoescape']);
    }

    public function testEnableStripCommentsSetsDefaultDirectiveTrue(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableStripComments();

        self::assertTrue($configurator->defaultDirectives['lumi.strip_comments']);
    }

    public function testDisableStripCommentsSetsDefaultDirectiveFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disableStripComments();

        self::assertFalse($configurator->defaultDirectives['lumi.strip_comments']);
    }

    public function testEnableAlwaysCompileSetsTrue(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableAlwaysCompile();

        self::assertTrue($configurator->viewAlwaysCompile);
    }

    public function testDisableAlwaysCompileSetsFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableAlwaysCompile();
        $configurator->disableAlwaysCompile();

        self::assertFalse($configurator->viewAlwaysCompile);
    }

    public function testEnableErrorReportingSetsViewDisableErrorReportingFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableErrorReporting();

        self::assertFalse($configurator->viewDisableErrorReporting);
    }

    public function testDisableErrorReportingSetsViewDisableErrorReportingTrue(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->enableErrorReporting();
        $configurator->disableErrorReporting();

        self::assertTrue($configurator->viewDisableErrorReporting);
    }

    public function testDeclareAddsToDirectives(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->declare('my.directive', 'hello');

        self::assertSame('hello', $configurator->directives['my.directive']);
    }

    public function testDeclareAcceptsVariousValueTypes(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->declare('int_val', 42);
        $configurator->declare('float_val', 3.14);
        $configurator->declare('bool_val', true);
        $configurator->declare('null_val', null);

        self::assertSame(42, $configurator->directives['int_val']);
        self::assertSame(3.14, $configurator->directives['float_val']);
        self::assertTrue($configurator->directives['bool_val']);
        self::assertNull($configurator->directives['null_val']);
    }

    public function testAllowAllFunctionsSetsAllowAllPolicy(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->allowAllFunctions();

        self::assertSame(RuntimeFunctionPolicy::ALLOW_ALL, $configurator->functionPolicy);
    }

    public function testDisallowAllFunctionsSetsDisallowAllPolicy(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disallowAllFunctions();

        self::assertSame(RuntimeFunctionPolicy::DISALLOW_ALL, $configurator->functionPolicy);
    }

    public function testDisallowAllFunctionsClearsPhpFunctions(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->addFunction('strlen');

        $configurator->disallowAllFunctions();

        self::assertSame([], $configurator->phpFunctions);
    }

    public function testAddFunctionRegistersPhpFunctionByName(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->addFunction('strlen');

        self::assertArrayHasKey('strlen', $configurator->phpFunctions);
        self::assertSame('strlen', $configurator->phpFunctions['strlen']->name);
        self::assertNull($configurator->phpFunctions['strlen']->mappedName);
    }

    public function testAddFunctionRecordsMappedName(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->addFunction(
            name: 'uppercase',
            mappedName: 'strtoupper',
        );

        self::assertSame('strtoupper', $configurator->phpFunctions['uppercase']->mappedName);
    }

    public function testAddFunctionRegistersAliases(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->addFunction(
            name: 'uppercase',
            aliases: [
                'upper',
                'to_upper',
            ],
        );

        self::assertArrayHasKey('upper', $configurator->phpFunctions);
        self::assertArrayHasKey('to_upper', $configurator->phpFunctions);
    }

    public function testAddFunctionLowercasesLookupKeys(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->addFunction('StrLen');

        self::assertArrayHasKey('strlen', $configurator->phpFunctions);
    }

    public function testAddFunctionResetsDisallowAllToCustomOnly(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disallowAllFunctions();
        $configurator->addFunction('strlen');

        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $configurator->functionPolicy);
    }

    public function testAddFunctionDoesNotChangeAllowAllPolicy(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->allowAllFunctions();
        $configurator->addFunction('strlen');

        self::assertSame(RuntimeFunctionPolicy::ALLOW_ALL, $configurator->functionPolicy);
    }

    public function testWithFunctionClassResetsDisallowAllToCustomOnly(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disallowAllFunctions();
        $configurator->withFunctionClass(StubFunctionClass::class);

        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $configurator->functionPolicy);
    }

    public function testWithoutStandardLibrarySetsFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutStandardLibrary();

        self::assertFalse($configurator->withStandardLibrary);
    }

    public function testAllowAllInstanceCallsClearsInstanceCallClasses(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withAllowedInstanceCall(\stdClass::class);
        $configurator->allowAllInstanceCalls();

        self::assertSame([], $configurator->instanceCallClasses);
    }

    public function testWithAllowedInstanceCallAddsClasses(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withAllowedInstanceCall(\stdClass::class, \ArrayObject::class);

        self::assertContains(\stdClass::class, $configurator->instanceCallClasses);
        self::assertContains(\ArrayObject::class, $configurator->instanceCallClasses);
    }

    public function testWithAllowedInstanceCallMergesMultipleCalls(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withAllowedInstanceCall(\stdClass::class);
        $configurator->withAllowedInstanceCall(\ArrayObject::class);

        self::assertContains(\stdClass::class, $configurator->instanceCallClasses);
        self::assertContains(\ArrayObject::class, $configurator->instanceCallClasses);
    }

    public function testUseLoaderSetsLoader(): void
    {
        $configurator = $this->makeConfigurator();
        $loader = new Loader(
            directory: '',
            cacheDirectory: '',
            extension: '',
        );
        $configurator->useLoader($loader);

        self::assertSame($loader, $configurator->loader);
    }

    public function testWithoutOptimizersClearsAllOptimizers(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutOptimizers();

        self::assertSame([], $configurator->optimizers);
    }

    public function testWithHighlightThemeRegistersThemeByIdentifier(): void
    {
        $configurator = $this->makeConfigurator();
        $theme = new LumiDark();

        $configurator->withHighlightTheme($theme);

        self::assertArrayHasKey($theme->identifier, $configurator->highlightThemes);
        self::assertSame($theme, $configurator->highlightThemes[$theme->identifier]);
    }

    public function testWithHighlightThemeAddsMultipleThemes(): void
    {
        $configurator = $this->makeConfigurator();
        $first = $this->makeStubTheme(
            identifier: 'brand-a',
            color: '#000',
        );

        $second = $this->makeStubTheme(
            identifier: 'brand-b',
            color: '#fff',
        );

        $configurator->withHighlightTheme($first, $second);

        self::assertSame($first, $configurator->highlightThemes['brand-a']);
        self::assertSame($second, $configurator->highlightThemes['brand-b']);
    }

    public function testWithHighlightThemeLastWriteWinsOnDuplicateIdentifier(): void
    {
        $configurator = $this->makeConfigurator();
        $first = $this->makeStubTheme(
            identifier: 'brand',
            color: '#000',
        );

        $second = $this->makeStubTheme(
            identifier: 'brand',
            color: '#fff',
        );

        $configurator->withHighlightTheme($first);
        $configurator->withHighlightTheme($second);

        self::assertCount(1, $configurator->highlightThemes);
        self::assertSame($second, $configurator->highlightThemes['brand']);
    }

    public function testBuildPropagatesCustomThemeIntoCompilerHighlighter(): void
    {
        $configurator = $this->makeConfigurator();
        $theme = $this->makeStubTheme(
            identifier: 'brand-magenta',
            color: '#ff00ff',
        );

        $configurator->withHighlightTheme($theme);
        $render = $configurator->build();

        self::assertInstanceOf(LumiViewRender::class, $render);

        $output = $render->runtime->engine->compiler->highlighter->highlightString(
            theme: 'brand-magenta',
            source: 'hello',
        );

        self::assertStringContainsString('#ff00ff', $output);
    }

    public function testBuildPropagatesCustomThemeIntoEngineHighlighter(): void
    {
        $configurator = $this->makeConfigurator();
        $theme = $this->makeStubTheme(
            identifier: 'brand-cyan',
            color: '#00ffff',
        );

        $configurator->withHighlightTheme($theme);
        $render = $configurator->build();

        self::assertInstanceOf(LumiViewRender::class, $render);

        $output = $render->runtime->engine->highlightString(
            source: 'hello',
            theme: 'brand-cyan',
        );

        self::assertStringContainsString('#00ffff', $output);
    }

    private function makeStubTheme(
        string $identifier,
        string $color,
    ): ThemeInterface {
        return new class ($identifier, $color) implements ThemeInterface {
            public function __construct(
                public string $identifier,
                private readonly string $color,
            ) {
            }

            public function color(
                ColorSlot $slot,
            ): string {
                return $this->color;
            }
        };
    }

    public function testWithCustomOptimizerAddsOptimizer(): void
    {
        $configurator = $this->makeConfigurator();
        $optimizer = new RecordingOptimizer();
        $configurator->withCustomOptimizer($optimizer);

        self::assertArrayHasKey(RecordingOptimizer::class, $configurator->optimizers);
        self::assertSame($optimizer, $configurator->optimizers[RecordingOptimizer::class]);
    }

    public function testWithCustomOptimizerAddsMultipleOptimizers(): void
    {
        $configurator = $this->makeConfigurator();

        $first = new RecordingOptimizer(
            changeCount: 1,
        );

        $second = new RecordingOptimizer(
            changeCount: 2,
        );

        $configurator->withoutOptimizers();
        $configurator->withCustomOptimizer($first, $second);

        self::assertCount(1, $configurator->optimizers);
        self::assertSame($second, $configurator->optimizers[RecordingOptimizer::class]);
    }

    public function testValidateReturnsTrueWhenBothDirectoriesExist(): void
    {
        $configurator = $this->makeConfigurator();
        $dir = \sys_get_temp_dir();

        $configurator->viewDirectory($dir);
        $configurator->cacheDirectory($dir);

        self::assertTrue($configurator->validate());
    }

    public function testValidateReturnsFalseWhenViewDirectoryDoesNotExist(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->viewDirectory('/nonexistent/path/views');
        $configurator->cacheDirectory(\sys_get_temp_dir());

        self::assertFalse($configurator->validate());
    }

    public function testValidateReturnsFalseWhenCacheDirectoryDoesNotExist(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->viewDirectory(\sys_get_temp_dir());
        $configurator->cacheDirectory('/nonexistent/path/cache');

        self::assertFalse($configurator->validate());
    }

    public function testWithFunctionClassRegistersAttributedMethodsIntoIntrospector(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFunctionClass(StubFunctionClass::class);

        $render = $configurator->build();
        $introspector = $render->runtime->engine->compiler->introspector;

        self::assertTrue($introspector->hasFunction('stub_upper'));
        self::assertTrue($introspector->hasFunction('stub_context'));
        self::assertTrue($introspector->hasFunction('stub_ctx'));

        $withContext = $introspector->getFunction('stub_context');

        self::assertTrue($withContext->wantsContext);
        self::assertSame(0, $withContext->contextParameterIndex);
    }

    public function testWithFilterClassRegistersAttributedMethodsIntoIntrospector(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFilterClass(StubFilterClass::class);

        $render = $configurator->build();
        $introspector = $render->runtime->engine->compiler->introspector;

        self::assertTrue($introspector->hasFilter('stub_upper'));
        self::assertTrue($introspector->hasFilter('stub_context'));
        self::assertTrue($introspector->hasFilter('stub_ctx'));

        $withContext = $introspector->getFilter('stub_context');

        self::assertTrue($withContext->wantsContext);
        self::assertSame(1, $withContext->contextParameterIndex);
    }

    public function testBuildInstancesTableStartsEmptyForFilterClasses(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFilterClass(StubFilterClass::class);

        $render = $configurator->build();

        self::assertArrayNotHasKey(StubFilterClass::class, $render->runtime->instances);
    }

    public function testBuildInstancesTableStartsEmptyForFunctionClasses(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFunctionClass(StubFunctionClass::class);

        $render = $configurator->build();

        self::assertArrayNotHasKey(StubFunctionClass::class, $render->runtime->instances);
    }

    public function testBuildInstanceResolverResolvesDiscoveredFilterClass(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFilterClass(StubFilterClass::class);

        $render = $configurator->build();
        $resolver = $render->runtime->instanceResolver;

        self::assertInstanceOf(StubFilterClass::class, $resolver(StubFilterClass::class));
    }

    public function testBuildInstanceResolverResolvesDiscoveredFunctionClass(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFunctionClass(StubFunctionClass::class);

        $render = $configurator->build();
        $resolver = $render->runtime->instanceResolver;

        self::assertInstanceOf(StubFunctionClass::class, $resolver(StubFunctionClass::class));
    }

    public function testBuildFilterDispatcherSplicesContextArgument(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFilterClass(StubFilterClass::class);

        $render = $configurator->build();
        $dispatcher = $render->runtime->filterDispatchers['stub_context'];

        self::assertSame(
            'hello:ctx',
            $dispatcher(
                [
                    'hello',
                ],
                $render->runtime,
            ),
        );
    }

    public function testBuildFunctionDispatcherSplicesContextArgument(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withFunctionClass(StubFunctionClass::class);

        $render = $configurator->build();
        $dispatcher = $render->runtime->functionDispatchers['stub_context'];

        self::assertSame(
            'hello:ctx',
            $dispatcher(
                [
                    'hello',
                ],
                $render->runtime,
            ),
        );
    }

    public function testBuildPopulatesLoaderWithAutoComputedConfigurationHash(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutStandardLibrary();

        $render = $configurator->build();

        self::assertSame(
            LumiHashBuilder::fromConfiguratorStandalone($configurator),
            $render->loader->configurationHash,
        );
    }

    public function testWithConfigurationHashOverridesAutoCompute(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutStandardLibrary();
        $configurator->withConfigurationHash('explicit-override-hash');

        $render = $configurator->build();

        self::assertSame(
            'explicit-override-hash',
            $render->loader->configurationHash,
        );
    }

    public function testBuildLeavesHostSuppliedLoaderUntouched(): void
    {
        $hostLoader = new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
            configurationHash: 'host-hash',
        );

        $configurator = $this->makeConfigurator();
        $configurator->withoutStandardLibrary();
        $configurator->useLoader($hostLoader);

        $render = $configurator->build();

        self::assertSame($hostLoader, $render->loader);
        self::assertSame('host-hash', $render->loader->configurationHash);
    }

    public function testBuildThrowsWhenHostLoaderAndConfigurationHashBothSupplied(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutStandardLibrary();
        $configurator->useLoader(new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
        ));
        $configurator->withConfigurationHash('explicit');

        self::expectException(LumiException::class);

        $configurator->build();
    }

    public function testBuildReturnsViewRenderInterface(): void
    {
        $configurator = $this->makeConfigurator();

        self::assertInstanceOf(ViewRenderInterface::class, $configurator->build());
    }

    public function testBuildWithModifiedDefaultDirectivesCreatesCustomCompiler(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutStandardLibrary();
        $configurator->disableAutoescape();

        self::assertInstanceOf(ViewRenderInterface::class, $configurator->build());
    }
}
