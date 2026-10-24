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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubLumiEngine;
use Tuxxedo\View\Lumi\LumiEngine;
use Tuxxedo\View\Lumi\LumiViewRender;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewException;

class LumiViewRenderTest extends TestCase
{
    private string $viewsDir;
    private string $cacheDir;
    private StubLumiEngine $engine;
    private int $initialBufferLevel = 0;

    /**
     * @var string[]
     */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->initialBufferLevel = \ob_get_level();
        $this->viewsDir = $this->createTempDirectory('views');
        $this->cacheDir = $this->createTempDirectory('cache');
        $this->engine = new StubLumiEngine();
    }

    protected function tearDown(): void
    {
        while (\ob_get_level() > $this->initialBufferLevel) {
            \ob_end_clean();
        }

        foreach (\array_reverse($this->tempPaths) as $path) {
            $this->removeRecursive($path);
        }

        $this->tempPaths = [];
    }

    private function createTempDirectory(
        string $prefix,
    ): string {
        $path = \tempnam(\sys_get_temp_dir(), 'tuxxedo_lumi_render_' . $prefix . '_');

        if ($path === false) {
            throw new \RuntimeException('Failed to allocate a temporary path');
        }

        \unlink($path);
        \mkdir($path, 0755, true);

        $normalized = \str_replace('\\', '/', $path);
        $this->tempPaths[] = $normalized;

        return $normalized;
    }

    private function removeRecursive(
        string $path,
    ): void {
        if (!\is_dir($path)) {
            if (\is_file($path)) {
                @\unlink($path);
            }

            return;
        }

        $entries = \glob($path . '/*');

        if (\is_array($entries)) {
            foreach ($entries as $entry) {
                $this->removeRecursive($entry);
            }
        }

        @\rmdir($path);
    }

    private function createLoader(): Loader
    {
        return new Loader(
            directory: $this->viewsDir,
            cacheDirectory: $this->cacheDir,
            extension: '.lumi',
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $directives
     */
    private function createRuntime(
        array $directives = [],
        RuntimeFunctionPolicy $functionPolicy = RuntimeFunctionPolicy::ALLOW_ALL,
    ): Runtime {
        return new Runtime(
            engine: $this->engine,
            instanceResolver: static fn (string $class): object => new $class(),
            directives: $directives,
            functionPolicy: $functionPolicy,
        );
    }

    private function createRender(
        ?Runtime $runtime = null,
        bool $alwaysCompile = false,
        bool $disableErrorReporting = true,
    ): LumiViewRender {
        return new LumiViewRender(
            loader: $this->createLoader(),
            runtime: $runtime ?? $this->createRuntime(),
            alwaysCompile: $alwaysCompile,
            disableErrorReporting: $disableErrorReporting,
        );
    }

    private function writeView(
        string $name,
        string $contents = 'source',
    ): void {
        \file_put_contents($this->viewsDir . '/' . $name . '.lumi', $contents);
    }

    private function writeCachedFile(
        string $name,
        string $php,
    ): void {
        \file_put_contents($this->cacheDir . '/' . $name . '.php', $php);
    }

    public function testConstructorRegistersItselfWithRuntime(): void
    {
        $runtime = $this->createRuntime();
        $render = $this->createRender(
            runtime: $runtime,
        );

        self::assertSame($render, $runtime->renderer);
    }

    public function testConstructorExposesLoaderRuntimeAndAlwaysCompile(): void
    {
        $runtime = $this->createRuntime();
        $loader = $this->createLoader();
        $render = new LumiViewRender(
            loader: $loader,
            runtime: $runtime,
            alwaysCompile: true,
        );

        self::assertSame($loader, $render->loader);
        self::assertSame($runtime, $render->runtime);
        self::assertTrue($render->alwaysCompile);
    }

    public function testConstructorDefaultsAlwaysCompileToFalse(): void
    {
        $render = $this->createRender();

        self::assertFalse($render->alwaysCompile);
    }

    public function testGetViewFileNameDelegatesToLoader(): void
    {
        $render = $this->createRender();

        self::assertSame(
            $this->viewsDir . '/home.lumi',
            $render->getViewFileName('home'),
        );
    }

    public function testViewExistsDelegatesToLoaderForExistingView(): void
    {
        $this->writeView('home');
        $render = $this->createRender();

        self::assertTrue($render->viewExists('home'));
    }

    public function testViewExistsDelegatesToLoaderForMissingView(): void
    {
        $render = $this->createRender();

        self::assertFalse($render->viewExists('missing'));
    }

    public function testRenderThrowsViewExceptionWhenViewDoesNotExist(): void
    {
        $render = $this->createRender();

        self::expectException(ViewException::class);

        $render->render(
            view: new View(
                name: 'missing',
            ),
        );
    }

    public function testRenderReturnsBufferOutputFromCachedFile(): void
    {
        $this->writeView('home');
        $this->writeCachedFile('home', '<?php echo "rendered-output"; ?>');

        $render = $this->createRender();

        self::assertSame(
            'rendered-output',
            $render->render(
                view: new View(
                    name: 'home',
                ),
            ),
        );
    }

    public function testRenderCompilesViewWhenCachedFileMissing(): void
    {
        $this->writeView('home');
        $this->engine->compileSourceCode = '<?php echo "freshly-compiled"; ?>';

        $render = $this->createRender();

        $output = $render->render(
            view: new View(
                name: 'home',
            ),
        );

        self::assertSame('freshly-compiled', $output);
        self::assertSame(1, $this->engine->compileCallCount);
        self::assertSame($this->viewsDir . '/home.lumi', $this->engine->lastCompiledFile);
        self::assertFileExists($this->cacheDir . '/home.php');
    }

    public function testRenderSkipsCompileWhenCachedAndAlwaysCompileFalse(): void
    {
        $this->writeView('home');
        $this->writeCachedFile('home', '<?php echo "cached-output"; ?>');

        $render = $this->createRender();

        $render->render(
            view: new View(
                name: 'home',
            ),
        );

        self::assertSame(0, $this->engine->compileCallCount);
    }

    public function testRenderRecompilesEveryTimeWhenAlwaysCompileTrue(): void
    {
        $this->writeView('home');
        $this->writeCachedFile('home', '<?php echo "stale"; ?>');
        $this->engine->compileSourceCode = '<?php echo "fresh"; ?>';

        $render = $this->createRender(
            alwaysCompile: true,
        );

        $first = $render->render(
            view: new View(
                name: 'home',
            ),
        );
        $second = $render->render(
            view: new View(
                name: 'home',
            ),
        );

        self::assertSame('fresh', $first);
        self::assertSame('fresh', $second);
        self::assertSame(2, $this->engine->compileCallCount);
    }

    public function testRenderViewScopeIsExposedAsLumiVariablesInsideCompiledFile(): void
    {
        $this->writeView('greeting');
        $this->writeCachedFile(
            'greeting',
            '<?php echo $__lumiVariables[\'name\']; ?>',
        );

        $render = $this->createRender();

        self::assertSame(
            'Kalle',
            $render->render(
                view: new View(
                    name: 'greeting',
                    scope: [
                        'name' => 'Kalle',
                    ],
                ),
            ),
        );
    }

    public function testRenderBindsRenderFrameToRuntimeAsThis(): void
    {
        $this->writeView('functioncall');
        $this->writeCachedFile(
            'functioncall',
            '<?php echo $this->functionCall(\'strtoupper\', [\'hello\']); ?>',
        );

        $render = $this->createRender();

        self::assertSame(
            'HELLO',
            $render->render(
                view: new View(
                    name: 'functioncall',
                ),
            ),
        );
    }

    public function testRenderPushesDirectivesAndBlocksOntoRuntimeStack(): void
    {
        $this->writeView('directive');
        $this->writeCachedFile(
            'directive',
            '<?php echo $this->directives[\'page.title\']; ?>',
        );

        $runtime = $this->createRuntime(
            directives: [
                'page.title' => 'outer',
            ],
        );
        $render = $this->createRender(
            runtime: $runtime,
        );

        $output = $render->render(
            view: new View(
                name: 'directive',
            ),
            directives: [
                'page.title' => 'inner',
            ],
        );

        self::assertSame('inner', $output);
    }

    public function testRenderPopsStateAfterSuccessfulRender(): void
    {
        $this->writeView('home');
        $this->writeCachedFile('home', '<?php echo "ok"; ?>');

        $runtime = $this->createRuntime(
            directives: [
                'page.title' => 'original',
            ],
        );
        $render = $this->createRender(
            runtime: $runtime,
        );

        $render->render(
            view: new View(
                name: 'home',
            ),
            directives: [
                'page.title' => 'replaced',
            ],
        );

        self::assertSame('original', $runtime->directives['page.title']);
    }

    public function testRenderPopsStateAfterRenderException(): void
    {
        $this->writeView('boom');
        $this->writeCachedFile(
            'boom',
            '<?php throw new \RuntimeException(\'kaboom\'); ?>',
        );

        $runtime = $this->createRuntime(
            directives: [
                'k' => 'before',
            ],
        );
        $render = $this->createRender(
            runtime: $runtime,
        );

        try {
            $render->render(
                view: new View(
                    name: 'boom',
                ),
                directives: [
                    'k' => 'during',
                ],
            );

            self::fail('Expected ViewException to be thrown');
        } catch (ViewException) {
        }

        self::assertSame('before', $runtime->directives['k']);
    }

    public function testRenderWrapsExceptionFromCompiledFileAsViewException(): void
    {
        $this->writeView('boom');
        $this->writeCachedFile(
            'boom',
            '<?php throw new \RuntimeException(\'kaboom\'); ?>',
        );

        $render = $this->createRender();

        self::expectException(ViewException::class);

        $render->render(
            view: new View(
                name: 'boom',
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: bool}>
     */
    public static function errorReportingFlagDataProvider(): \Generator
    {
        yield 'error reporting disabled' => [
            true,
        ];

        yield 'error reporting enabled' => [
            false,
        ];
    }

    #[DataProvider('errorReportingFlagDataProvider')]
    public function testRenderUnwindsOutputBuffersWhenCompiledFileThrows(
        bool $disableErrorReporting,
    ): void {
        $this->writeView('partial_boom');
        $this->writeCachedFile(
            'partial_boom',
            '<?php echo "partial output"; \ob_start(); echo "nested"; throw new \RuntimeException(\'kaboom\'); ?>',
        );

        $render = $this->createRender(
            disableErrorReporting: $disableErrorReporting,
        );

        \ob_start();

        $level = \ob_get_level();

        try {
            $render->render(
                view: new View(
                    name: 'partial_boom',
                ),
            );

            self::fail('Expected a ViewException');
        } catch (ViewException) {
            self::assertSame($level, \ob_get_level());
        } finally {
            $leaked = \ob_get_clean();
        }

        self::assertSame('', $leaked);
    }

    public function testRenderRestoresErrorReportingWhenCompiledFileThrows(): void
    {
        $this->writeView('boom');
        $this->writeCachedFile(
            'boom',
            '<?php throw new \RuntimeException(\'kaboom\'); ?>',
        );

        $render = $this->createRender();
        $previous = \error_reporting();

        try {
            $render->render(
                view: new View(
                    name: 'boom',
                ),
            );

            self::fail('Expected a ViewException');
        } catch (ViewException) {
            self::assertSame($previous, \error_reporting());
        } finally {
            \error_reporting($previous);
        }
    }

    public function testRenderDisablesErrorReportingWhenFlagIsTrue(): void
    {
        $this->writeView('reporting');
        $this->writeCachedFile(
            'reporting',
            '<?php echo (string) \error_reporting(); ?>',
        );

        $render = $this->createRender();

        $previous = \error_reporting();

        try {
            $output = $render->render(
                view: new View(
                    name: 'reporting',
                ),
            );

            self::assertSame('0', $output);
            self::assertSame($previous, \error_reporting());
        } finally {
            \error_reporting($previous);
        }
    }

    public function testRenderLeavesErrorReportingIntactWhenFlagIsFalse(): void
    {
        $this->writeView('reporting');
        $this->writeCachedFile(
            'reporting',
            '<?php echo (string) \error_reporting(); ?>',
        );

        $render = $this->createRender(
            disableErrorReporting: false,
        );

        $previous = \error_reporting();

        try {
            $output = $render->render(
                view: new View(
                    name: 'reporting',
                ),
            );

            self::assertSame((string) $previous, $output);
        } finally {
            \error_reporting($previous);
        }
    }

    /**
     * @param array<string, mixed> $scope
     */
    private function renderCompiledSource(
        string $source,
        RuntimeFunctionPolicy $functionPolicy,
        array $scope = [],
    ): string {
        $this->writeView('compiled');
        $this->writeCachedFile(
            'compiled',
            LumiEngine::createDefault()->compileString($source),
        );

        return $this->createRender(
            runtime: $this->createRuntime(
                functionPolicy: $functionPolicy,
            ),
        )->render(
            view: new View(
                name: 'compiled',
                scope: $scope,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function rangeTemplateDataProvider(): \Generator
    {
        yield 'ascending integers' => [
            '{% for i in 1..5 %}{! i !}{% endfor %}',
            [],
            '12345',
        ];

        yield 'descending integers' => [
            '{% for i in 5..1 %}{! i !}{% endfor %}',
            [],
            '54321',
        ];

        yield 'negative bounds' => [
            '{% for i in -2..2 %}{! i !},{% endfor %}',
            [],
            '-2,-1,0,1,2,',
        ];

        yield 'equal bounds' => [
            '{% for i in 3..3 %}{! i !}{% endfor %}',
            [],
            '3',
        ];

        yield 'letters' => [
            '{% for c in \'a\'..\'e\' %}{! c !}{% endfor %}',
            [],
            'abcde',
        ];

        yield 'floats' => [
            '{% for x in 0.5..2 %}{! x !};{% endfor %}',
            [],
            '0.5;1.5;',
        ];

        yield 'variable bounds' => [
            '{% for i in first..last %}{! i !}{% endfor %}',
            [
                'first' => 2,
                'last' => 4,
            ],
            '234',
        ];

        yield 'arithmetic end' => [
            '{% for i in 0..n - 1 %}{! i !}{% endfor %}',
            [
                'n' => 3,
            ],
            '012',
        ];

        yield 'nested ranges' => [
            '{% for i in 1..2 %}{% for j in 1..2 %}{! i ~ j !},{% endfor %}{% endfor %}',
            [],
            '11,12,21,22,',
        ];

        yield 'key and value' => [
            '{% for v, k in 7..9 %}{! k !}={! v !},{% endfor %}',
            [],
            '0=7,1=8,2=9,',
        ];

        yield 'indexed group' => [
            '{! (10..20)[3] !}',
            [],
            '13',
        ];

        yield 'assigned range' => [
            '{% set pages = 1..3 %}{% for p in pages %}{! p !}{% endfor %}',
            [],
            '123',
        ];

        yield 'ternary branch' => [
            '{% for i in up ? 1..3 : 3..1 %}{! i !}{% endfor %}',
            [
                'up' => false,
            ],
            '321',
        ];
    }

    /**
     * @return \Generator<string, array{0: RuntimeFunctionPolicy, 1: string, 2: array<string, mixed>, 3: string}>
     */
    public static function rangePolicyDataProvider(): \Generator
    {
        foreach (RuntimeFunctionPolicy::cases() as $policy) {
            foreach (self::rangeTemplateDataProvider() as $name => [$source, $scope, $expected]) {
                yield $policy->name . ': ' . $name => [
                    $policy,
                    $source,
                    $scope,
                    $expected,
                ];
            }
        }
    }

    /**
     * @param array<string, mixed> $scope
     */
    #[DataProvider('rangePolicyDataProvider')]
    public function testRangeRendersUnderEveryFunctionPolicy(
        RuntimeFunctionPolicy $functionPolicy,
        string $source,
        array $scope,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $this->renderCompiledSource(
                source: $source,
                functionPolicy: $functionPolicy,
                scope: $scope,
            ),
        );
    }

    public function testExplicitRangeCallStillFollowsFunctionPolicy(): void
    {
        self::expectException(ViewException::class);

        $this->renderCompiledSource(
            source: '{% for i in range(1, 3) %}{! i !}{% endfor %}',
            functionPolicy: RuntimeFunctionPolicy::DISALLOW_ALL,
        );
    }

    public function testExplicitRangeCallRendersWhenFunctionsAreAllowed(): void
    {
        self::assertSame(
            '123',
            $this->renderCompiledSource(
                source: '{% for i in range(1, 3) %}{! i !}{% endfor %}',
                functionPolicy: RuntimeFunctionPolicy::ALLOW_ALL,
            ),
        );
    }
}
