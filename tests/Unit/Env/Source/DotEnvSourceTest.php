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

namespace Unit\Env\Source;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\Source\DotEnvSource;

class DotEnvSourceTest extends TestCase
{
    private const string FIXTURE_PATH = __DIR__ . '/../../../Fixture/Env/simple.env';

    public function testHasReturnsTrueForParsedKey(): void
    {
        self::assertTrue(
            (new DotEnvSource(
                file: self::FIXTURE_PATH,
            ))->has(
                key: 'FOO',
            ),
        );
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        self::assertFalse(
            (new DotEnvSource(
                file: self::FIXTURE_PATH,
            ))->has(
                key: 'NOT_THERE',
            ),
        );
    }

    public function testGetReturnsParsedStringValue(): void
    {
        self::assertSame(
            'bar',
            (new DotEnvSource(
                file: self::FIXTURE_PATH,
            ))->get(
                key: 'FOO',
            ),
        );
    }

    public function testGetReturnsParsedIntegerValue(): void
    {
        self::assertSame(
            42,
            (new DotEnvSource(
                file: self::FIXTURE_PATH,
            ))->get(
                key: 'NUM',
            ),
        );
    }

    public function testGetReturnsParsedBooleanValue(): void
    {
        self::assertTrue(
            (new DotEnvSource(
                file: self::FIXTURE_PATH,
            ))->get(
                key: 'FLAG',
            ),
        );
    }

    public function testGetThrowsForMissingKey(): void
    {
        $this->expectException(EnvException::class);

        (new DotEnvSource(
            file: self::FIXTURE_PATH,
        ))->get(
            key: 'NOT_THERE',
        );
    }

    public function testConstructorThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(EnvException::class);

        new DotEnvSource(
            file: __DIR__ . '/does-not-exist.env',
        );
    }

    public function testConstructorPropagatesParserExceptions(): void
    {
        $tmp = \tempnam(\sys_get_temp_dir(), 'tuxxedo-env-');

        if ($tmp === false) {
            self::fail('Could not create temporary file');
        }

        \file_put_contents($tmp, "1BAD=value\n");

        try {
            new DotEnvSource(
                file: $tmp,
            );

            self::fail('Expected EnvException');
        } catch (EnvException $exception) {
            self::assertStringContainsString(
                '1BAD',
                $exception->getMessage(),
            );
        } finally {
            \unlink($tmp);
        }
    }
}
