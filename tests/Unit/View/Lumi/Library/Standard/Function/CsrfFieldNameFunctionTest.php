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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Support\Security\Csrf\Storage\StubCsrfStorageHandler;
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\Container\Container;
use Tuxxedo\Security\Csrf\CsrfManager;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFieldNameFunction;

class CsrfFieldNameFunctionTest extends TestCase
{
    private function makeContainerWithCsrfManager(
        string $fieldName = '__csrf_token',
    ): Container {
        return (new Container())->singleton(
            class: new CsrfManager(
                storage: new StubCsrfStorageHandler(),
                fieldName: $fieldName,
            ),
        );
    }

    public function testCallReturnsDefaultFieldName(): void
    {
        $function = new CsrfFieldNameFunction(
            container: $this->makeContainerWithCsrfManager(),
        );

        self::assertSame(
            '__csrf_token',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsCustomFieldName(): void
    {
        $function = new CsrfFieldNameFunction(
            container: $this->makeContainerWithCsrfManager(
                fieldName: 'authenticity_token',
            ),
        );

        self::assertSame(
            'authenticity_token',
            $function->call(
                [],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
