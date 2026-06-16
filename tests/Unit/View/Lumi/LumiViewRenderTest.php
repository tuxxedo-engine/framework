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

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubLumiEngine;
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
        // @todo Rewrite this entire block to use tempnam()
        $base = \str_replace('\\', '/', \sys_get_temp_dir());
        $path = $base . '/tuxxedo_lumi_render_' . $prefix . '_' . \uniqid('', true);

        \mkdir($path, 0755, true);

        $this->tempPaths[] = $path;

        return $path;
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
    ): Runtime {
        return new Runtime(
            engine: $this->engine,
            directives: $directives,
            functionPolicy: RuntimeFunctionPolicy::ALLOW_ALL,
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
}
