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

namespace Unit\File;

use PHPUnit\Framework\TestCase;
use Tuxxedo\File\FileCollectionFactory;
use Tuxxedo\File\FileException;
use Tuxxedo\File\FileInterface;
use Tuxxedo\File\LocalFile;

class FileCollectionFactoryTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryRoots = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryRoots as $root) {
            $this->removeTree(
                path: $root,
            );
        }

        $this->temporaryRoots = [];
    }

    private function removeTree(
        string $path,
    ): void {
        if (!\file_exists($path) && !\is_link($path)) {
            return;
        }

        if (\is_dir($path) && !\is_link($path)) {
            $entries = \scandir($path);

            if ($entries === false) {
                @\rmdir($path);

                return;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $this->removeTree(
                    path: $path . '/' . $entry,
                );
            }

            @\rmdir($path);

            return;
        }

        @\unlink($path);
    }

    private function makeRoot(): string
    {
        $root = \sys_get_temp_dir() . '/tuxxedo-fcf-' . \bin2hex(\random_bytes(6));

        if (!\mkdir($root, 0755, true)) {
            self::fail('Failed to create temporary root');
        }

        $this->temporaryRoots[] = $root;

        $resolved = \realpath($root);

        return \str_replace(
            '\\',
            '/',
            $resolved !== false ? $resolved : $root,
        );
    }

    private function writeFile(
        string $root,
        string $relative,
        string $bytes = '',
    ): string {
        $absolute = $root . '/' . $relative;
        $parent = \dirname($absolute);

        if (!\is_dir($parent)) {
            \mkdir($parent, 0755, true);
        }

        \file_put_contents($absolute, $bytes);

        return $absolute;
    }

    public function testPathsRejectsNonDirectoryRoot(): void
    {
        $this->expectException(FileException::class);

        (void) FileCollectionFactory::paths(
            directory: '/nonexistent/' . \bin2hex(\random_bytes(8)),
        );
    }

    public function testPathsReturnsAllFilesRecursivelyByDefault(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'a.txt', 'a');
        $this->writeFile($root, 'nested/b.txt', 'b');
        $this->writeFile($root, 'nested/deeper/c.txt', 'c');

        $paths = FileCollectionFactory::paths($root)->toArray();

        \sort($paths);

        self::assertSame(
            [
                $root . '/a.txt',
                $root . '/nested/b.txt',
                $root . '/nested/deeper/c.txt',
            ],
            $paths,
        );
    }

    public function testPathsFiltersByExtensionAtRootLevel(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'app.php', '<?php');
        $this->writeFile($root, 'notes.md', '# notes');
        $this->writeFile($root, 'nested/inner.php', '<?php');

        $paths = FileCollectionFactory::paths($root, '*.php')->toArray();

        self::assertSame(
            [
                $root . '/app.php',
            ],
            $paths,
        );
    }

    public function testPathsRecursivePatternMatchesNestedFiles(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'app.php', '<?php');
        $this->writeFile($root, 'controllers/user.php', '<?php');
        $this->writeFile($root, 'controllers/admin/dashboard.php', '<?php');

        $paths = FileCollectionFactory::paths($root, '**/*.php')->toArray();

        \sort($paths);

        self::assertSame(
            [
                $root . '/app.php',
                $root . '/controllers/admin/dashboard.php',
                $root . '/controllers/user.php',
            ],
            $paths,
        );
    }

    public function testPathsExcludesDirectories(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'file.txt', 'x');
        \mkdir($root . '/empty-dir');

        $paths = FileCollectionFactory::paths($root)->toArray();

        self::assertSame(
            [
                $root . '/file.txt',
            ],
            $paths,
        );
    }

    public function testPathsEmptyForEmptyDirectory(): void
    {
        $root = $this->makeRoot();

        $paths = FileCollectionFactory::paths($root)->toArray();

        self::assertSame(
            [],
            $paths,
        );
    }

    public function testPathsPatternMissesNonMatching(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'a.txt', 'a');
        $this->writeFile($root, 'b.md', 'b');

        $paths = FileCollectionFactory::paths($root, '*.php')->toArray();

        self::assertSame(
            [],
            $paths,
        );
    }

    public function testNativePathsReturnsPathsWithSystemSeparator(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'a.txt', 'a');
        $this->writeFile($root, 'nested/b.txt', 'b');

        $paths = FileCollectionFactory::nativePaths($root)->toArray();

        \sort($paths);

        $nativeRoot = \str_replace('/', \DIRECTORY_SEPARATOR, $root);

        self::assertSame(
            [
                $nativeRoot . \DIRECTORY_SEPARATOR . 'a.txt',
                $nativeRoot . \DIRECTORY_SEPARATOR . 'nested' . \DIRECTORY_SEPARATOR . 'b.txt',
            ],
            $paths,
        );
    }

    public function testNativePathsRejectsNonDirectoryRoot(): void
    {
        $this->expectException(FileException::class);

        (void) FileCollectionFactory::nativePaths(
            directory: '/nonexistent/' . \bin2hex(\random_bytes(8)),
        );
    }

    public function testFilesReturnsLocalFileInstances(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'a.txt', 'first');
        $this->writeFile($root, 'b.txt', 'second');

        $files = FileCollectionFactory::files($root)->toArray();

        self::assertCount(2, $files);

        foreach ($files as $file) {
            self::assertInstanceOf(FileInterface::class, $file);
            self::assertInstanceOf(LocalFile::class, $file);
        }
    }

    public function testFilesContentsRoundTrip(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'greeting.txt', 'hello');

        $files = FileCollectionFactory::files($root)->toArray();

        self::assertCount(1, $files);
        self::assertSame(
            'hello',
            $files[0]->contents(),
        );
    }

    public function testFilesFiltersByPattern(): void
    {
        $root = $this->makeRoot();

        $this->writeFile($root, 'keep.txt', 'k');
        $this->writeFile($root, 'skip.md', 's');

        $files = FileCollectionFactory::files($root, '*.txt')->toArray();

        self::assertCount(1, $files);
        self::assertSame('keep.txt', $files[0]->name);
    }

    public function testFilesRejectsNonDirectoryRoot(): void
    {
        $this->expectException(FileException::class);

        (void) FileCollectionFactory::files(
            directory: '/nonexistent/' . \bin2hex(\random_bytes(8)),
        );
    }
}
