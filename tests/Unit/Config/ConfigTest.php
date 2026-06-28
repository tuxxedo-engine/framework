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

namespace Unit\Config;

use Fixture\Config\Typed\KeyOverrideConfigInterface;
use Fixture\Config\Typed\NestedConfigInterface;
use Fixture\Config\Typed\SimpleConfigInterface;
use Fixture\Config\Typed\UnnamespacedConfigInterface;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigException;
use Tuxxedo\Container\Container;

class ConfigTest extends TestCase
{
    private const string LOADER = __DIR__ . '/../../Fixture/Config/Loader';

    public function testHasReturnsTrueForExistingPath(): void
    {
        $config = new Config(
            directives: [
                'key' => 'value',
            ],
        );

        self::assertTrue($config->has('key'));
    }

    public function testHasReturnsFalseForMissingPath(): void
    {
        $config = new Config(
            directives: [
                'key' => 'value',
            ],
        );

        self::assertFalse($config->has('missing'));
    }

    public function testPathReturnsValueForNonDottedKey(): void
    {
        $config = new Config(
            directives: [
                'key' => 'value',
            ],
        );

        self::assertSame('value', $config->path('key'));
    }

    public function testPathReturnsLeafScalarFromDottedPath(): void
    {
        $config = new Config(
            directives: [
                'outer' => [
                    'inner' => 'leaf-value',
                ],
            ],
        );

        self::assertSame('leaf-value', $config->path('outer.inner'));
    }

    public function testPathReturnsSubtreeWhenDottedPathLeafIsArray(): void
    {
        $config = new Config(
            directives: [
                'outer' => [
                    'inner' => [
                        'deep' => 'value',
                    ],
                ],
            ],
        );

        self::assertSame(
            [
                'deep' => 'value',
            ],
            $config->path('outer.inner'),
        );
    }

    public function testPathThrowsForMissingDottedSegment(): void
    {
        $config = new Config(
            directives: [
                'outer' => [
                    'inner' => 'value',
                ],
            ],
        );

        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Invalid configuration directive "outer.missing"');

        $config->path('outer.missing');
    }

    public function testPathThrowsForMissingNonDottedKey(): void
    {
        $config = new Config(
            directives: [
                'key' => 'value',
            ],
        );

        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Invalid configuration directive "missing"');

        $config->path('missing');
    }

    public function testPathThrowsForEmptyString(): void
    {
        $config = new Config(
            directives: [
                'key' => 'value',
            ],
        );

        self::expectException(ConfigException::class);

        $config->path('');
    }

    public function testCreateFromFileWithRawArray(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/raw.php');

        self::assertSame('one', $config->path('first'));
        self::assertSame('two', $config->path('second'));
        self::assertSame('deep', $config->path('nested.leaf'));
    }

    public function testCreateFromFileWithScalarCastsToArray(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/scalar.php');

        self::assertTrue($config->has('0'));
        self::assertSame('scalar-value', $config->path('0'));
    }

    public function testCreateFromFileWithDeferredArrayClosure(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/deferred-array.php');

        self::assertSame('from-closure', $config->path('deferred'));
    }

    public function testCreateFromFileWithDeferredNoReturnTypeClosure(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/deferred-no-return.php');

        self::assertSame('closure', $config->path('untyped'));
    }

    public function testCreateFromFileWithDeferredUnionReturnTypeClosure(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/deferred-union.php');

        self::assertSame('closure', $config->path('union'));
    }

    public function testCreateFromFileWithDeferredClosureResolvingTypedDependencies(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/deferred-with-dep.php');

        self::assertSame('injected', $config->path('dep'));
    }

    public function testCreateFromFileWithDeferredClosureReturningScalarCastsToArray(): void
    {
        $config = Config::createFromFile(new Container(), self::LOADER . '/deferred-scalar.php');

        self::assertTrue($config->has('0'));
        self::assertSame('just-a-scalar', $config->path('0'));
    }

    public function testCreateFromFileWithTypedConfigRegistersAndFlattens(): void
    {
        $container = new Container();
        $config = Config::createFromFile($container, self::LOADER . '/typed.php');

        $typed = $container->resolve(SimpleConfigInterface::class);

        self::assertSame('fixture', $typed->name);
        self::assertSame(7, $typed->count);

        self::assertSame('fixture', $config->path('simple.name'));
        self::assertSame(7, $config->path('simple.count'));
    }

    public function testCreateFromFileWithTypedConfigKeyOverrideAppliesAliasedKey(): void
    {
        $container = new Container();
        $config = Config::createFromFile($container, self::LOADER . '/key-override.php');

        self::assertSame('aliased', $container->resolve(KeyOverrideConfigInterface::class)->sourceProperty);
        self::assertSame('aliased', $config->path('override.renamed'));
        self::assertFalse($config->has('override.sourceProperty'));
    }

    public function testCreateFromFileWithNestedNamespaceTypedConfigCreatesNestedDirectives(): void
    {
        $container = new Container();
        $config = Config::createFromFile($container, self::LOADER . '/nested.php');

        self::assertSame('nested-value', $container->resolve(NestedConfigInterface::class)->label);
        self::assertSame('nested-value', $config->path('outer.inner.deep.label'));
    }

    public function testCreateFromFileWithUnnamespacedTypedConfigRegistersButDoesNotFlatten(): void
    {
        $container = new Container();
        $config = Config::createFromFile($container, self::LOADER . '/unnamespaced.php');

        self::assertSame('no-namespace-attribute', $container->resolve(UnnamespacedConfigInterface::class)->value);
        self::assertFalse($config->has('value'));
    }

    public function testCreateFromFileWithDuplicateLeafKeysThrows(): void
    {
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Duplicate configuration key "duplicate.shared"');

        Config::createFromFile(new Container(), self::LOADER . '/duplicate-leaf-key.php');
    }

    public function testCreateFromDirectoryAggregatesMixedEntries(): void
    {
        $container = new Container();
        $config = Config::createFromDirectory($container, self::LOADER . '/Directory');

        self::assertSame('value', $config->path('raw.plain'));
        self::assertSame('deferred-value', $config->path('deferred.deferred-key'));
        self::assertSame('directory', $config->path('simple.name'));
        self::assertSame(3, $container->resolve(SimpleConfigInterface::class)->count);
    }

    public function testCreateFromDirectoryWithDuplicateTypedConfigInterfaceThrows(): void
    {
        self::expectException(ConfigException::class);
        self::expectExceptionMessageMatches('/Duplicate configuration namespace/');

        Config::createFromDirectory(new Container(), self::LOADER . '/DuplicateNamespace');
    }

    public function testCreateFromDirectoryWithIntermediateNamespaceCollisionThrows(): void
    {
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Duplicate configuration namespace "outer.inner.deep"');

        Config::createFromDirectory(new Container(), self::LOADER . '/IntermediateCollision');
    }

    public function testCreateFromDirectoryWithLeafNamespaceCollisionThrows(): void
    {
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Duplicate configuration namespace "simple"');

        Config::createFromDirectory(new Container(), self::LOADER . '/LeafCollision');
    }
}
