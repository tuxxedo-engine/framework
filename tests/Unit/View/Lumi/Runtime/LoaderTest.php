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

namespace Unit\View\Lumi\Runtime;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Runtime\Loader;

class LoaderTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (\is_file($file)) {
                @\unlink($file);
            }
        }

        $this->createdFiles = [];
    }

    private function tempDir(): string
    {
        return \str_replace('\\', '/', \sys_get_temp_dir());
    }

    private function uniqueName(): string
    {
        return 'tuxxedo_loader_' . \uniqid('', true);
    }

    public function testConstructorExposesProperties(): void
    {
        $loader = new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
        );

        self::assertSame('/views', $loader->directory);
        self::assertSame('/cache', $loader->cacheDirectory);
        self::assertSame('.lumi', $loader->extension);
    }

    public function testGetViewFileNameJoinsDirectoryViewAndExtension(): void
    {
        $loader = new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
        );

        self::assertSame(
            '/views/home.lumi',
            $loader->getViewFileName('home'),
        );
    }

    public function testGetCachedFileNameAppendsPhpExtension(): void
    {
        $loader = new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
        );

        self::assertSame(
            '/cache/home.php',
            $loader->getCachedFileName('home'),
        );
    }

    public function testGetCachedFileNameSupportsNestedViewName(): void
    {
        $loader = new Loader(
            directory: '/views',
            cacheDirectory: '/cache',
            extension: '.lumi',
        );

        self::assertSame(
            '/cache/admin/dashboard.php',
            $loader->getCachedFileName('admin/dashboard'),
        );
    }

    public function testExistsReturnsTrueForExistingViewFile(): void
    {
        $name = $this->uniqueName();
        $file = $this->tempDir() . '/' . $name . '.lumi';

        \file_put_contents($file, 'hello');

        $this->createdFiles[] = $file;

        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertTrue($loader->exists($name));
    }

    public function testExistsReturnsFalseForMissingViewFile(): void
    {
        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertFalse(
            $loader->exists($this->uniqueName()),
        );
    }

    public function testIsCachedReturnsTrueWhenCachedFileExists(): void
    {
        $name = $this->uniqueName();
        $file = $this->tempDir() . '/' . $name . '.php';

        \file_put_contents($file, '<?php');

        $this->createdFiles[] = $file;

        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertTrue($loader->isCached($name));
    }

    public function testIsCachedReturnsFalseWhenCachedFileMissing(): void
    {
        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertFalse(
            $loader->isCached($this->uniqueName()),
        );
    }

    public function testInvalidateReturnsTrueWhenCachedFileWasNotPresent(): void
    {
        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertTrue(
            $loader->invalidate($this->uniqueName()),
        );
    }

    public function testInvalidateRemovesCachedFile(): void
    {
        $name = $this->uniqueName();
        $file = $this->tempDir() . '/' . $name . '.php';

        \file_put_contents($file, '<?php');

        $this->createdFiles[] = $file;

        $loader = new Loader(
            directory: $this->tempDir(),
            cacheDirectory: $this->tempDir(),
            extension: '.lumi',
        );

        self::assertTrue($loader->invalidate($name));
        self::assertFileDoesNotExist($file);
    }
}
