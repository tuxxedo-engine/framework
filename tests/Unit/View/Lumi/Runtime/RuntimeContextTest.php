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

use Fixture\View\Lumi\Runtime\RecordingFilter;
use Fixture\View\Lumi\Runtime\RecordingFunction;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubLumiEngine;
use Support\View\Lumi\Runtime\StubRenderer;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeContext;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;

class RuntimeContextTest extends TestCase
{
    private Runtime $runtime;
    private RuntimeContext $context;

    protected function setUp(): void
    {
        $this->runtime = new Runtime(
            engine: new StubLumiEngine(),
            directives: [
                'lumi.autoescape' => true,
            ],
            functions: [
                'strtoupper' => new RecordingFunction(
                    returnValue: 'fn-result',
                ),
            ],
            filters: [
                'upper' => new RecordingFilter(
                    returnValue: 'FILTERED',
                ),
            ],
            functionPolicy: RuntimeFunctionPolicy::CUSTOM_ONLY,
        );

        $this->runtime->renderer(
            new StubRenderer(
                loader: new Loader(
                    directory: \sys_get_temp_dir(),
                    cacheDirectory: \sys_get_temp_dir(),
                    extension: '.lumi',
                ),
                runtime: $this->runtime,
            ),
        );

        $this->runtime->block(
            'header',
            static function (array $scope): void {
            },
        );

        $this->context = new RuntimeContext(
            runtime: $this->runtime,
        );
    }

    public function testFunctionPolicyMirrorsRuntimePolicy(): void
    {
        self::assertSame(
            RuntimeFunctionPolicy::CUSTOM_ONLY,
            $this->context->functionPolicy,
        );
    }

    public function testHasDirectiveReturnsTrueForKnownDirective(): void
    {
        self::assertTrue($this->context->hasDirective('lumi.autoescape'));
    }

    public function testHasDirectiveReturnsFalseForUnknownDirective(): void
    {
        self::assertFalse($this->context->hasDirective('lumi.unknown'));
    }

    public function testDirectiveReturnsKnownValue(): void
    {
        self::assertTrue($this->context->directive('lumi.autoescape'));
    }

    public function testDirectiveThrowsOnUnknownDirective(): void
    {
        self::expectException(RuntimeException::class);

        $this->context->directive('lumi.missing');
    }

    public function testHasFilterDelegatesToRuntime(): void
    {
        self::assertTrue($this->context->hasFilter('upper'));
        self::assertFalse($this->context->hasFilter('missing'));
    }

    public function testCallFilterDelegatesToRuntime(): void
    {
        self::assertSame(
            'FILTERED',
            $this->context->callFilter('hello', 'upper'),
        );
    }

    public function testHasFunctionLowercasesLookupName(): void
    {
        self::assertTrue($this->context->hasFunction('STRTOUPPER'));
        self::assertTrue($this->context->hasFunction('Strtoupper'));
        self::assertFalse($this->context->hasFunction('missing'));
    }

    public function testCallFunctionDelegatesToRuntime(): void
    {
        self::assertSame(
            'fn-result',
            $this->context->callFunction(
                'strtoupper',
                [
                    'arg',
                ],
            ),
        );
    }

    public function testHasBlockDelegatesToRuntime(): void
    {
        self::assertTrue($this->context->hasBlock('header'));
        self::assertFalse($this->context->hasBlock('missing'));
    }
}
