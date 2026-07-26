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

namespace Unit\File\Storage\Local;

use PHPUnit\Framework\TestCase;
use Tuxxedo\File\FileFactory;
use Tuxxedo\File\Storage\Local\Config\LocalStorageConfig;
use Tuxxedo\File\Storage\Local\LocalStorage;
use Tuxxedo\File\Storage\StorageException;
use Tuxxedo\File\Storage\StorageInterface;

class LocalStorageTest extends TestCase
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
                // @codeCoverageIgnoreStart
                @\rmdir($path);

                return;
                // @codeCoverageIgnoreEnd
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

    /**
     * @throws StorageException
     */
    private function makeStorage(
        bool $autoCreateDirectories = true,
    ): StorageInterface {
        $root = \sys_get_temp_dir() . '/tuxxedo-storage-' . \bin2hex(\random_bytes(6));

        if (!\mkdir($root, 0755, true)) {
            self::fail('Failed to create temporary storage root');
        }

        $this->temporaryRoots[] = $root;

        return new LocalStorage(
            config: new LocalStorageConfig(
                root: $root,
                autoCreateDirectories: $autoCreateDirectories,
                allowCaseInsensitiveFilesystem: true,
            ),
        );
    }

    public function testConstructorRejectsMissingRoot(): void
    {
        $this->expectException(StorageException::class);

        new LocalStorage(
            config: new LocalStorageConfig(
                root: '/nonexistent/' . \bin2hex(\random_bytes(8)),
            ),
        );
    }

    public function testWriteThenReadRoundTrip(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'greeting.txt',
            file: FileFactory::fromBytes(
                bytes: 'hello world',
                name: 'greeting.txt',
                mimeType: 'text/plain',
            ),
        );

        $file = $storage->read(
            key: 'greeting.txt',
        );

        self::assertSame(
            'hello world',
            $file->contents(),
        );
    }

    public function testExistsReturnsTrueAfterWrite(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'existent.txt',
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );

        self::assertTrue(
            $storage->exists(
                key: 'existent.txt',
            ),
        );
    }

    public function testExistsReturnsFalseForMissingKey(): void
    {
        $storage = $this->makeStorage();

        self::assertFalse(
            $storage->exists(
                key: 'missing.txt',
            ),
        );
    }

    public function testDeleteRemovesFile(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'ephemeral.txt',
            file: FileFactory::fromBytes(
                bytes: 'gone soon',
            ),
        );

        $storage->delete(
            key: 'ephemeral.txt',
        );

        self::assertFalse(
            $storage->exists(
                key: 'ephemeral.txt',
            ),
        );
    }

    public function testDeleteThrowsForMissingKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        $storage->delete(
            key: 'never-existed.txt',
        );
    }

    public function testReadThrowsForMissingKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        (void) $storage->read(
            key: 'missing.txt',
        );
    }

    public function testWriteAutoCreatesNestedDirectories(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'avatars/2026/07/x.png',
            file: FileFactory::fromBytes(
                bytes: 'pixels',
            ),
        );

        self::assertTrue(
            $storage->exists(
                key: 'avatars/2026/07/x.png',
            ),
        );
    }

    public function testWriteThrowsWhenAutoCreateDisabledAndParentMissing(): void
    {
        $storage = $this->makeStorage(
            autoCreateDirectories: false,
        );

        $this->expectException(StorageException::class);

        $storage->write(
            key: 'never/materialised/x.txt',
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );
    }

    public function testWriteAcceptsLocalFileFastPath(): void
    {
        $storage = $this->makeStorage();

        $source = \tempnam(\sys_get_temp_dir(), 'tuxxedo-source-');

        if ($source === false) {
            self::fail('Failed to allocate temporary source file');
        }

        \file_put_contents($source, 'source bytes');

        try {
            $storage->write(
                key: 'copied.txt',
                file: FileFactory::fromPath(
                    path: $source,
                ),
            );

            $file = $storage->read(
                key: 'copied.txt',
            );

            self::assertSame(
                'source bytes',
                $file->contents(),
            );
        } finally {
            @\unlink($source);
        }
    }

    public function testReadRejectsTraversalKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        (void) $storage->read(
            key: '../etc/passwd',
        );
    }

    public function testWriteRejectsTraversalKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        $storage->write(
            key: 'ok/../../../etc/passwd',
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );
    }

    public function testWriteRejectsNullByteKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        $storage->write(
            key: "ok\x00.txt",
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );
    }

    public function testWriteRejectsBackslashKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        $storage->write(
            key: 'ok\\bad.txt',
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );
    }

    public function testWriteRejectsEmptyKey(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(StorageException::class);

        $storage->write(
            key: '',
            file: FileFactory::fromBytes(
                bytes: 'x',
            ),
        );
    }

    public function testExistsReturnsFalseForInvalidKey(): void
    {
        $storage = $this->makeStorage();

        self::assertFalse(
            $storage->exists(
                key: '../etc/passwd',
            ),
        );
    }

    public function testListReturnsAllKeys(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'a.txt',
            file: FileFactory::fromBytes(
                bytes: 'a',
            ),
        );
        $storage->write(
            key: 'nested/b.txt',
            file: FileFactory::fromBytes(
                bytes: 'b',
            ),
        );
        $storage->write(
            key: 'nested/deeper/c.txt',
            file: FileFactory::fromBytes(
                bytes: 'c',
            ),
        );

        $keys = \iterator_to_array(
            $storage->list(),
            preserve_keys: false,
        );

        \sort($keys);

        self::assertSame(
            [
                'a.txt',
                'nested/b.txt',
                'nested/deeper/c.txt',
            ],
            $keys,
        );
    }

    public function testListFiltersByPrefixPattern(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'avatars/x.png',
            file: FileFactory::fromBytes(
                bytes: 'a',
            ),
        );
        $storage->write(
            key: 'avatars/y.png',
            file: FileFactory::fromBytes(
                bytes: 'b',
            ),
        );
        $storage->write(
            key: 'users/x.png',
            file: FileFactory::fromBytes(
                bytes: 'c',
            ),
        );

        $keys = \iterator_to_array(
            $storage->list(
                pattern: 'avatars/**',
            ),
            preserve_keys: false,
        );

        \sort($keys);

        self::assertSame(
            [
                'avatars/x.png',
                'avatars/y.png',
            ],
            $keys,
        );
    }

    public function testListSingleLevelExcludesDeeperKeys(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'avatars/x.png',
            file: FileFactory::fromBytes(
                bytes: 'a',
            ),
        );
        $storage->write(
            key: 'avatars/nested/y.png',
            file: FileFactory::fromBytes(
                bytes: 'b',
            ),
        );

        $keys = \iterator_to_array(
            $storage->list(
                pattern: 'avatars/*',
            ),
            preserve_keys: false,
        );

        self::assertSame(
            [
                'avatars/x.png',
            ],
            $keys,
        );
    }

    public function testListReturnsEmptyWhenPrefixDirectoryMissing(): void
    {
        $storage = $this->makeStorage();

        $keys = \iterator_to_array(
            $storage->list(
                pattern: 'nonexistent/**',
            ),
            preserve_keys: false,
        );

        self::assertSame(
            [],
            $keys,
        );
    }

    public function testListWithLiteralRootLevelPatternScansFromRoot(): void
    {
        $storage = $this->makeStorage();

        $storage->write(
            key: 'file.txt',
            file: FileFactory::fromBytes(
                bytes: 'root level',
            ),
        );
        $storage->write(
            key: 'other.txt',
            file: FileFactory::fromBytes(
                bytes: 'sibling',
            ),
        );

        $keys = \iterator_to_array(
            $storage->list(
                pattern: 'file.txt',
            ),
            preserve_keys: false,
        );

        self::assertSame(
            [
                'file.txt',
            ],
            $keys,
        );
    }
}
