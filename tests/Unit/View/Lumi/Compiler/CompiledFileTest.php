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

namespace Unit\View\Lumi\Compiler;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Compiler\CompiledFile;
use Tuxxedo\View\Lumi\Compiler\CompilerException;

class CompiledFileTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            if (\is_file($path)) {
                @\unlink($path);
            }
        }

        foreach ($this->createdPaths as $path) {
            $directory = \dirname($path);

            while (
                $directory !== '' &&
                $directory !== '.' &&
                $directory !== \DIRECTORY_SEPARATOR &&
                $directory !== \sys_get_temp_dir() &&
                \is_dir($directory)
            ) {
                if (!@\rmdir($directory)) {
                    break;
                }

                $directory = \dirname($directory);
            }
        }

        $this->createdPaths = [];
    }

    private function reservePath(
        string $path,
    ): string {
        $this->createdPaths[] = $path;

        return $path;
    }

    private function uniqueTempPath(
        string $suffix = '',
    ): string {
        return $this->reservePath(
            \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . \uniqid('tuxxedo_compiled_', true) . $suffix,
        );
    }

    public function testConstructorExposesSourceFileAndSourceCode(): void
    {
        $compiledFile = new CompiledFile(
            sourceFile: 'views/home.lumi',
            sourceCode: '<?php echo 1;',
        );

        self::assertSame('views/home.lumi', $compiledFile->sourceFile);
        self::assertSame('<?php echo 1;', $compiledFile->sourceCode);
    }

    public function testSaveToWritesSourceCodeToExistingDirectory(): void
    {
        $target = $this->uniqueTempPath('.php');

        $compiledFile = new CompiledFile(
            sourceFile: 'views/home.lumi',
            sourceCode: '<?php echo "hello";',
        );

        $result = $compiledFile->saveTo(
            file: $target,
        );

        self::assertTrue($result);
        self::assertFileExists($target);
        self::assertSame('<?php echo "hello";', \file_get_contents($target));
    }

    public function testSaveToCreatesMissingParentDirectories(): void
    {
        $directory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . \uniqid('tuxxedo_compiled_dir_', true);
        $target = $this->reservePath(
            $directory . \DIRECTORY_SEPARATOR . 'nested' . \DIRECTORY_SEPARATOR . 'view.php',
        );

        $compiledFile = new CompiledFile(
            sourceFile: 'views/nested.lumi',
            sourceCode: '<?php echo "nested";',
        );

        $result = $compiledFile->saveTo(
            file: $target,
        );

        self::assertTrue($result);
        self::assertFileExists($target);
    }

    public function testSaveWritesSourceCodeWithoutThrowing(): void
    {
        $target = $this->uniqueTempPath('.php');

        $compiledFile = new CompiledFile(
            sourceFile: 'views/home.lumi',
            sourceCode: '<?php echo "ok";',
        );

        $compiledFile->save(
            file: $target,
        );

        self::assertFileExists($target);
        self::assertSame('<?php echo "ok";', \file_get_contents($target));
    }

    public function testSaveThrowsCompilerExceptionWhenWriteFails(): void
    {
        $compiledFile = new CompiledFile(
            sourceFile: 'views/home.lumi',
            sourceCode: '<?php echo "boom";',
        );

        self::expectException(CompilerException::class);

        $compiledFile->save(
            file: \sys_get_temp_dir(),
        );
    }
}
