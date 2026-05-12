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

use Fixture\View\Lumi\LumiConfigurator\StubConfig;
use Fixture\View\Lumi\LumiConfigurator\StubFilterProvider;
use Fixture\View\Lumi\LumiConfigurator\StubFunctionProvider;
use Fixture\View\Lumi\LumiConfigurator\StubLibraryDiscovery;
use Fixture\View\Lumi\LumiConfigurator\StubLibraryProvider;
use Fixture\View\Lumi\RecordingOptimizer;
use Fixture\View\Lumi\Runtime\RecordingFilter;
use Fixture\View\Lumi\Runtime\RecordingFunction;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\View\Lumi\Library\Directive\DefaultDirectives;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\Optimizer\Dce\DceOptimizer;
use Tuxxedo\View\Lumi\Optimizer\Sccp\SccpOptimizer;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\ViewRenderInterface;

class LumiConfiguratorTest extends TestCase
{
    private function makeContainer(): Container
    {
        return new Container();
    }

    private function makeConfigurator(): LumiConfigurator
    {
        return new LumiConfigurator(
            container: $this->makeContainer(),
        );
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

    /**
     * @param array<string, mixed> $data
     */
    private function makeContainerWithConfig(array $data): Container
    {
        $container = new Container();
        $config = new StubConfig($data);

        $container->persistent($config);
        $container->alias(ConfigInterface::class, StubConfig::class);

        return $container;
    }

    public function testFromConfigSetsViewDirectoryWhenKeyPresent(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.directory' => '/tmp/views',
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertSame('/tmp/views', $configurator->viewDirectory);
    }

    public function testFromConfigSetsCacheDirectoryWhenKeyPresent(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.cacheDirectory' => '/tmp/cache',
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertSame('/tmp/cache', $configurator->viewCacheDirectory);
    }

    public function testFromConfigSetsViewExtensionWhenKeyPresent(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.extension' => 'lumi',
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertSame('lumi', $configurator->viewExtension);
    }

    public function testFromConfigEnablesAlwaysCompileWhenTrue(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.alwaysCompile' => true,
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertTrue($configurator->viewAlwaysCompile);
    }

    public function testFromConfigDisablesAlwaysCompileWhenFalse(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.alwaysCompile' => false,
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertFalse($configurator->viewAlwaysCompile);
    }

    public function testFromConfigDisablesErrorReportingWhenTrue(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.disableErrorReporting' => true,
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertTrue($configurator->viewDisableErrorReporting);
    }

    public function testFromConfigEnablesErrorReportingWhenFalse(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'view.disableErrorReporting' => false,
            ],
        );

        $configurator = LumiConfigurator::fromConfig($container);

        self::assertFalse($configurator->viewDisableErrorReporting);
    }

    public function testFromConfigSkipsKeysNotPresent(): void
    {
        $container = $this->makeContainerWithConfig([]);
        $configurator = LumiConfigurator::fromConfig($container);

        self::assertSame('', $configurator->viewDirectory);
        self::assertSame('', $configurator->viewCacheDirectory);
        self::assertSame('', $configurator->viewExtension);
        self::assertFalse($configurator->viewAlwaysCompile);
    }

    public function testFromConfigRespectsCustomNamespace(): void
    {
        $container = $this->makeContainerWithConfig(
            [
                'lumi.directory' => '/custom/views',
            ],
        );

        $configurator = LumiConfigurator::fromConfig(
            container: $container,
            namespace: 'lumi',
        );

        self::assertSame('/custom/views', $configurator->viewDirectory);
    }

    public function testFromConfigReturnsStaticInstance(): void
    {
        $container = $this->makeContainerWithConfig([]);
        $configurator = LumiConfigurator::fromConfig($container);

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

    public function testDisallowAllFunctionsClearsFunctions(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->defineFunction(
            handler: new RecordingFunction(name: 'fn'),
        );

        $configurator->disallowAllFunctions();

        self::assertSame([], $configurator->functions);
    }

    public function testDisallowAllFunctionsClearsFunctionProviders(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withFunctionProvider(
            new StubFunctionProvider(),
        );

        $configurator->disallowAllFunctions();

        self::assertSame([], $configurator->functionProviders);
    }

    public function testDefineFunctionAddsByName(): void
    {
        $configurator = $this->makeConfigurator();

        $fn = new RecordingFunction(
            name: 'my_fn',
        );

        $configurator->defineFunction(
            handler: $fn,
        );

        self::assertSame($fn, $configurator->functions['my_fn']);
    }

    public function testDefineFunctionAddsAliases(): void
    {
        $configurator = $this->makeConfigurator();

        $fn = new RecordingFunction(
            name: 'my_fn',
            aliases: [
                'alias_one',
                'alias_two',
            ],
        );

        $configurator->defineFunction(
            handler: $fn,
        );

        self::assertSame($fn, $configurator->functions['alias_one']);
        self::assertSame($fn, $configurator->functions['alias_two']);
    }

    public function testDefineFunctionResetsDisallowAllToCustomOnly(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disallowAllFunctions();
        $configurator->defineFunction(
            handler: new RecordingFunction(
                name: 'fn',
            ),
        );

        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $configurator->functionPolicy);
    }

    public function testDefineFunctionDoesNotChangeAllowAllPolicy(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->allowAllFunctions();
        $configurator->defineFunction(
            handler: new RecordingFunction(
                name: 'fn',
            ),
        );

        self::assertSame(RuntimeFunctionPolicy::ALLOW_ALL, $configurator->functionPolicy);
    }

    public function testAllowFunctionAddsPhpFunctionByName(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->allowFunction('strlen');

        self::assertInstanceOf(PhpFunction::class, $configurator->functions['strlen']);
    }

    public function testWithFunctionProviderAddsToProviders(): void
    {
        $configurator = $this->makeConfigurator();
        $provider = new StubFunctionProvider();

        $configurator->withFunctionProvider($provider);

        self::assertContains($provider, $configurator->functionProviders);
    }

    public function testWithFunctionProviderResetsDisallowAllToCustomOnly(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->disallowAllFunctions();
        $configurator->withFunctionProvider(
            new StubFunctionProvider(),
        );

        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $configurator->functionPolicy);
    }

    public function testDefineFilterAddsByName(): void
    {
        $configurator = $this->makeConfigurator();

        $filter = new RecordingFilter(
            name: 'my_filter',
        );

        $configurator->defineFilter(
            handler: $filter,
        );

        self::assertSame($filter, $configurator->customFilters['my_filter']);
    }

    public function testDefineFilterAddsAliases(): void
    {
        $configurator = $this->makeConfigurator();

        $filter = new RecordingFilter(
            name: 'my_filter',
            aliases: [
                'filter_alias',
            ],
        );

        $configurator->defineFilter(
            handler: $filter,
        );

        self::assertSame($filter, $configurator->customFilters['filter_alias']);
    }

    public function testWithFilterProviderAddsToProviders(): void
    {
        $configurator = $this->makeConfigurator();
        $provider = new StubFilterProvider();

        $configurator->withFilterProvider($provider);

        self::assertContains($provider, $configurator->filterProviders);
    }

    public function testWithLibraryDiscoveryRegistersLumiFunctions(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryDiscovery(),
        );

        self::assertArrayHasKey('stub_fn', $configurator->functions);
    }

    public function testWithLibraryDiscoveryRegistersLumiFunctionAliases(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryDiscovery(),
        );

        self::assertArrayHasKey('stub_fn_alias', $configurator->functions);
    }

    public function testWithLibraryDiscoveryRegistersLumiFilters(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(new StubLibraryDiscovery());

        self::assertArrayHasKey('stub_filter', $configurator->customFilters);
    }

    public function testWithLibraryDiscoveryRegistersLumiFilterAliases(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryDiscovery(),
        );

        self::assertArrayHasKey('stub_filter_alias', $configurator->customFilters);
    }

    public function testWithLibraryDiscoverySkipsUnannotatedMethods(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryDiscovery(),
        );

        self::assertArrayNotHasKey('unannotatedMethod', $configurator->functions);
        self::assertArrayNotHasKey('unannotatedMethod', $configurator->customFilters);
    }

    public function testWithLibraryProviderAddsFilterProvider(): void
    {
        $configurator = $this->makeConfigurator();
        $filterProvider = new StubFilterProvider();

        $configurator->withLibrary(
            new StubLibraryProvider(
                filterProvider: $filterProvider,
            ),
        );

        self::assertContains($filterProvider, $configurator->filterProviders);
    }

    public function testWithLibraryProviderAddsFunctionProvider(): void
    {
        $configurator = $this->makeConfigurator();
        $functionProvider = new StubFunctionProvider();

        $configurator->withLibrary(
            new StubLibraryProvider(
                functionProvider: $functionProvider,
            ),
        );

        self::assertContains($functionProvider, $configurator->functionProviders);
    }

    public function testWithLibraryProviderSkipsNullFilterProvider(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryProvider(
                filterProvider: null,
                functionProvider: new StubFunctionProvider(),
            ),
        );

        self::assertSame([], $configurator->filterProviders);
    }

    public function testWithLibraryProviderSkipsNullFunctionProvider(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withLibrary(
            new StubLibraryProvider(
                filterProvider: new StubFilterProvider(),
                functionProvider: null,
            ),
        );

        self::assertSame([], $configurator->functionProviders);
    }

    public function testWithStandardLibrarySetsTrue(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutStandardLibrary();
        $configurator->withStandardLibrary();

        self::assertTrue($configurator->withStandardLibrary);
    }

    public function testWithoutStandardLibrarySetsFalse(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutStandardLibrary();

        self::assertFalse($configurator->withStandardLibrary);
    }

    public function testWithAnyInstanceCallClearsInstanceCallClasses(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withAllowedInstanceCall(\stdClass::class);
        $configurator->withAnyInstanceCall();

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

    public function testUseLexerSetsLexer(): void
    {
        $configurator = $this->makeConfigurator();
        $lexer = LumiEngine::createDefaultLexer();
        $configurator->useLexer($lexer);

        self::assertSame($lexer, $configurator->lexer);
    }

    public function testUseParserSetsParser(): void
    {
        $configurator = $this->makeConfigurator();
        $parser = LumiEngine::createDefaultParser();
        $configurator->useParser($parser);

        self::assertSame($parser, $configurator->parser);
    }

    public function testUseCompilerSetsCompiler(): void
    {
        $configurator = $this->makeConfigurator();
        $compiler = LumiEngine::createDefaultCompiler();
        $configurator->useCompiler($compiler);

        self::assertSame($compiler, $configurator->compiler);
    }

    public function testUseHighlighterSetsHighlighter(): void
    {
        $configurator = $this->makeConfigurator();
        $highlighter = LumiEngine::createDefaultHighlighter();
        $configurator->useHighlighter($highlighter);

        self::assertSame($highlighter, $configurator->highlighter);
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

    public function testWithDceOptimizerAddsDceOptimizer(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutOptimizers();
        $configurator->withDceOptimizer();

        self::assertArrayHasKey(DceOptimizer::class, $configurator->optimizers);
        self::assertInstanceOf(DceOptimizer::class, $configurator->optimizers[DceOptimizer::class]);
    }

    public function testWithoutDceOptimizerRemovesDceOptimizer(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutDceOptimizer();

        self::assertArrayNotHasKey(DceOptimizer::class, $configurator->optimizers);
    }

    public function testWithSccpOptimizerAddsSccpOptimizer(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutOptimizers();
        $configurator->withSccpOptimizer();

        self::assertArrayHasKey(SccpOptimizer::class, $configurator->optimizers);
        self::assertInstanceOf(SccpOptimizer::class, $configurator->optimizers[SccpOptimizer::class]);
    }

    public function testWithoutSccpOptimizerRemovesSccpOptimizer(): void
    {
        $configurator = $this->makeConfigurator();
        $configurator->withoutSccpOptimizer();

        self::assertArrayNotHasKey(SccpOptimizer::class, $configurator->optimizers);
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

    public function testBuildWithExplicitCompilerUsesIt(): void
    {
        $configurator = $this->makeConfigurator();

        $configurator->withoutStandardLibrary();

        $compiler = LumiEngine::createDefaultCompiler();

        $configurator->useCompiler($compiler);

        self::assertInstanceOf(ViewRenderInterface::class, $configurator->build());
    }
}
