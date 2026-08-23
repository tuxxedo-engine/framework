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

namespace Unit\Router\Compiler;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\Compiler\PathCompiler;
use Tuxxedo\Router\Pattern\TypePatternRegistry;
use Tuxxedo\Router\Prefix;

class PathCompilerTest extends TestCase
{
    private function compiler(): PathCompiler
    {
        return new PathCompiler(
            patterns: TypePatternRegistry::createDefault(),
        );
    }

    public function testPlainPathWithoutArgumentsProducesLiteralRegexAndEmptyNodes(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users',
        );

        self::assertSame('#^/users$#', $compiled->regexPath);
        self::assertSame(
            [],
            $compiled->argumentNodes,
        );
    }

    public function testUnconstrainedArgumentFallsBackToNonSlashCatchAll(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users/{id}',
        );

        self::assertSame('#^/users/(?<id>[^/]+)$#', $compiled->regexPath);
        self::assertCount(1, $compiled->argumentNodes);
        self::assertSame('id', $compiled->argumentNodes[0]->name);
        self::assertSame(ArgumentKind::TYPED_IMPLICIT, $compiled->argumentNodes[0]->kind);
        self::assertNull($compiled->argumentNodes[0]->constraint);
        self::assertFalse($compiled->argumentNodes[0]->optional);
        self::assertFalse($compiled->argumentNodes[0]->prefixed);
    }

    public function testRegexConstraintIsInlinedIntoNamedGroup(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users/{id:\d+}',
        );

        self::assertSame('#^/users/(?<id>\d+)$#', $compiled->regexPath);
        self::assertCount(1, $compiled->argumentNodes);
        self::assertSame(ArgumentKind::REGEX, $compiled->argumentNodes[0]->kind);
        self::assertSame('\d+', $compiled->argumentNodes[0]->constraint);
    }

    public function testTypeConstraintResolvesFromTypePatternRegistry(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users/{id<numeric-id>}',
        );

        self::assertStringContainsString('(?<id>', $compiled->regexPath);
        self::assertCount(1, $compiled->argumentNodes);
        self::assertSame(ArgumentKind::TYPED_EXPLICIT, $compiled->argumentNodes[0]->kind);
        self::assertSame('numeric-id', $compiled->argumentNodes[0]->constraint);
    }

    public function testOptionalArgumentWrapsSlashAndSegmentInNonCapturingGroup(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users/{?id}',
        );

        self::assertSame('#^/users(?:/(?<id>[^/]+))?$#', $compiled->regexPath);
        self::assertCount(1, $compiled->argumentNodes);
        self::assertTrue($compiled->argumentNodes[0]->optional);
    }

    public function testMultipleArgumentsProduceOrderedNodes(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/users/{id:\d+}/posts/{slug}',
        );

        self::assertCount(2, $compiled->argumentNodes);
        self::assertSame('id', $compiled->argumentNodes[0]->name);
        self::assertSame(ArgumentKind::REGEX, $compiled->argumentNodes[0]->kind);
        self::assertSame('slug', $compiled->argumentNodes[1]->name);
        self::assertSame(ArgumentKind::TYPED_IMPLICIT, $compiled->argumentNodes[1]->kind);
    }

    public function testArgumentNamesInPrefixAreFlaggedPrefixed(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/tenants/{tenant}/users/{id}',
            prefix: new Prefix(
                path: '/tenants/{tenant}',
            ),
        );

        self::assertCount(2, $compiled->argumentNodes);
        self::assertSame('tenant', $compiled->argumentNodes[0]->name);
        self::assertTrue($compiled->argumentNodes[0]->prefixed);
        self::assertSame('id', $compiled->argumentNodes[1]->name);
        self::assertFalse($compiled->argumentNodes[1]->prefixed);
    }

    public function testUnknownTypeConstraintFallsBackToCatchAllPattern(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/x/{id<no-such-type>}',
        );

        self::assertSame('#^/x/(?<id>[^/]+)$#', $compiled->regexPath);
        self::assertSame(ArgumentKind::TYPED_EXPLICIT, $compiled->argumentNodes[0]->kind);
        self::assertSame('no-such-type', $compiled->argumentNodes[0]->constraint);
    }

    public function testEmittedRegexAnchorsAtStartAndEnd(): void
    {
        $compiled = $this->compiler()->compile(
            path: '/',
        );

        self::assertStringStartsWith('#^', $compiled->regexPath);
        self::assertStringEndsWith('$#', $compiled->regexPath);
    }
}
