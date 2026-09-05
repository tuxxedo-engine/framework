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

namespace Unit\Model;

use Fixture\Model\Polymorphic\MappedArticle;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\MorphTypeResolver;

class MorphTypeResolverTest extends TestCase
{
    public function testEncodeReturnsFqcnWhenTypeMapIsNull(): void
    {
        self::assertSame(
            MappedArticle::class,
            MorphTypeResolver::encode(
                class: MappedArticle::class,
                typeMap: null,
            ),
        );
    }

    public function testEncodeReturnsAliasWhenClassIsInTypeMap(): void
    {
        self::assertSame(
            'article',
            MorphTypeResolver::encode(
                class: MappedArticle::class,
                typeMap: [
                    'article' => MappedArticle::class,
                ],
            ),
        );
    }

    public function testEncodeFallsBackToFqcnWhenClassIsNotInTypeMap(): void
    {
        self::assertSame(
            MappedArticle::class,
            MorphTypeResolver::encode(
                class: MappedArticle::class,
                typeMap: [
                    'other' => \stdClass::class,
                ],
            ),
        );
    }

    public function testResolveReturnsClassFromTypeMap(): void
    {
        self::assertSame(
            MappedArticle::class,
            MorphTypeResolver::resolve(
                typeValue: 'article',
                typeMap: [
                    'article' => MappedArticle::class,
                ],
            ),
        );
    }

    public function testResolveReturnsNullWhenAliasNotInTypeMap(): void
    {
        self::assertNull(
            MorphTypeResolver::resolve(
                typeValue: 'unknown',
                typeMap: [
                    'article' => MappedArticle::class,
                ],
            ),
        );
    }

    public function testResolveReturnsFqcnWhenTypeMapIsNullAndClassExists(): void
    {
        self::assertSame(
            MappedArticle::class,
            MorphTypeResolver::resolve(
                typeValue: MappedArticle::class,
                typeMap: null,
            ),
        );
    }

    public function testResolveReturnsNullWhenTypeMapIsNullAndClassDoesNotExist(): void
    {
        self::assertNull(
            MorphTypeResolver::resolve(
                typeValue: 'App\\Model\\DoesNotExist',
                typeMap: null,
            ),
        );
    }
}
