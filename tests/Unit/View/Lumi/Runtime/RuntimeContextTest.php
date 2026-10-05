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
use Tuxxedo\Container\Container;
use Tuxxedo\View\Lumi\Compiler\Compiler;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableKind;
use Tuxxedo\View\Lumi\Runtime\Introspector\CallableMetadata;
use Tuxxedo\View\Lumi\Runtime\Introspector\RuntimeIntrospector;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeContext;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;

class RuntimeContextTest extends TestCase
{
    private RecordingFilter $filter;
    private RecordingFunction $function;
    private Runtime $runtime;
    private RuntimeContext $context;

    protected function setUp(): void
    {
        $this->filter = new RecordingFilter();
        $this->filter->returnValue = 'FILTERED';

        $this->function = new RecordingFunction();
        $this->function->returnValue = 'fn-result';

        $container = new Container();
        $container->singleton($this->filter);
        $container->singleton($this->function);

        $engine = new StubLumiEngine();
        $engine->compiler = Compiler::createWithDefaultProviders(
            introspector: new RuntimeIntrospector(
                functions: [
                    new CallableMetadata(
                        name: 'recording',
                        kind: CallableKind::TYPED_ATTRIBUTE,
                        className: RecordingFunction::class,
                        methodName: 'run',
                        wantsContext: true,
                        contextParameterIndex: 0,
                    ),
                ],
                filters: [
                    new CallableMetadata(
                        name: 'recording',
                        kind: CallableKind::TYPED_ATTRIBUTE,
                        className: RecordingFilter::class,
                        methodName: 'record',
                        wantsContext: true,
                        contextParameterIndex: 1,
                    ),
                ],
            ),
        );

        $this->runtime = new Runtime(
            engine: $engine,
            directives: [
                'lumi.autoescape' => true,
            ],
            phpFunctions: [
                'strtoupper' => new PhpFunction(
                    name: 'strtoupper',
                ),
            ],
            functionPolicy: RuntimeFunctionPolicy::CUSTOM_ONLY,
            container: $container,
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

    public function testHasFilterDelegatesToIntrospector(): void
    {
        self::assertTrue($this->context->hasFilter('recording'));
        self::assertFalse($this->context->hasFilter('missing'));
    }

    public function testCallFilterDelegatesToRuntime(): void
    {
        self::assertSame(
            'FILTERED',
            $this->context->callFilter('hello', 'recording'),
        );
    }

    public function testHasFunctionLowercasesPhpFunctionLookup(): void
    {
        self::assertTrue($this->context->hasFunction('STRTOUPPER'));
        self::assertTrue($this->context->hasFunction('Strtoupper'));
        self::assertFalse($this->context->hasFunction('missing'));
    }

    public function testHasFunctionConsultsIntrospector(): void
    {
        self::assertTrue($this->context->hasFunction('recording'));
    }

    public function testCallFunctionDispatchesPhpFunction(): void
    {
        self::assertSame(
            'HELLO',
            $this->context->callFunction(
                'strtoupper',
                [
                    'hello',
                ],
            ),
        );
    }

    public function testCallFunctionDispatchesTypedFunction(): void
    {
        self::assertSame(
            'fn-result',
            $this->context->callFunction(
                'recording',
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
