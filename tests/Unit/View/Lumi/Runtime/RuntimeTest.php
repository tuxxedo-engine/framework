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

use Fixture\View\Lumi\Runtime\OtherObject;
use Fixture\View\Lumi\Runtime\PlainObject;
use Fixture\View\Lumi\Runtime\RecordingFilter;
use Fixture\View\Lumi\Runtime\RecordingFunction;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubLumiEngine;
use Support\View\Lumi\Runtime\StubRenderer;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;

class RuntimeTest extends TestCase
{
    private StubLumiEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new StubLumiEngine();
    }

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param array<string, FunctionInterface> $functions
     * @param array<string, FilterInterface> $filters
     * @param array<class-string> $instanceCallClasses
     */
    private function createRuntime(
        RuntimeFunctionPolicy $policy = RuntimeFunctionPolicy::CUSTOM_ONLY,
        array $directives = [],
        array $functions = [],
        array $filters = [],
        array $instanceCallClasses = [],
    ): Runtime {
        return new Runtime(
            engine: $this->engine,
            directives: $directives,
            functions: $functions,
            filters: $filters,
            functionPolicy: $policy,
            instanceCallClasses: $instanceCallClasses,
        );
    }

    private function attachRenderer(
        Runtime $runtime,
    ): StubRenderer {
        $renderer = new StubRenderer(
            loader: new Loader(
                directory: \sys_get_temp_dir(),
                cacheDirectory: \sys_get_temp_dir(),
                extension: '.lumi',
            ),
            runtime: $runtime,
        );

        $runtime->renderer($renderer);

        return $renderer;
    }

    public function testConstructorExposesEngineAndDefaults(): void
    {
        $runtime = $this->createRuntime();

        self::assertSame($this->engine, $runtime->engine);
        self::assertSame(RuntimeFunctionPolicy::CUSTOM_ONLY, $runtime->functionPolicy);
        self::assertSame([], $runtime->instanceCallClasses);
        self::assertSame([], $runtime->blocks);
        self::assertSame([], $runtime->directives);
    }

    public function testConstructorLowercasesFunctionAndFilterKeys(): void
    {
        $runtime = $this->createRuntime(
            functions: [
                'UpperCase' => new RecordingFunction(),
            ],
            filters: [
                'TRIM' => new RecordingFilter(),
            ],
        );

        self::assertArrayHasKey('uppercase', $runtime->functions);
        self::assertArrayHasKey('trim', $runtime->filters);
    }

    public function testRendererSetterStoresRenderer(): void
    {
        $runtime = $this->createRuntime();
        $renderer = $this->attachRenderer($runtime);

        self::assertSame($renderer, $runtime->renderer);
    }

    public function testDirectiveSetsValue(): void
    {
        $runtime = $this->createRuntime();

        $runtime->directive('lumi.autoescape', false);

        self::assertSame(false, $runtime->directives['lumi.autoescape']);
    }

    public function testPushStateRecordsCurrentDirectivesAndBlocks(): void
    {
        $runtime = $this->createRuntime(
            directives: [
                'k' => 'a',
            ],
        );

        $runtime->block(
            'greeting',
            static function (array $scope): void {
            },
        );

        $runtime->pushState();

        self::assertCount(1, $runtime->directivesStack);
        self::assertCount(1, $runtime->blocksStack);
    }

    public function testPushStateAppliesProvidedDirectivesAndBlocks(): void
    {
        $runtime = $this->createRuntime(
            directives: [
                'k' => 'old',
            ],
        );

        $runtime->pushState(
            directives: [
                'k' => 'new',
            ],
            blocks: [
                'greeting' => static function (array $scope): void {
                },
            ],
        );

        self::assertSame('new', $runtime->directives['k']);
        self::assertArrayHasKey('greeting', $runtime->blocks);
    }

    public function testPopStateRestoresDirectivesAndBlocks(): void
    {
        $runtime = $this->createRuntime(
            directives: [
                'k' => 'first',
            ],
        );

        $runtime->pushState(
            directives: [
                'k' => 'second',
            ],
        );

        $runtime->popState();

        self::assertSame('first', $runtime->directives['k']);
    }

    public function testPopStateThrowsOnEmptyStack(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->popState();
    }

    public function testFunctionCallThrowsWhenPolicyDisallowAll(): void
    {
        $runtime = $this->createRuntime(
            policy: RuntimeFunctionPolicy::DISALLOW_ALL,
        );

        self::expectException(RuntimeException::class);

        $runtime->functionCall('strtoupper', ['hello']);
    }

    public function testFunctionCallThrowsForUnknownCustomFunctionUnderCustomOnlyPolicy(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->functionCall('strtoupper', ['hello']);
    }

    public function testFunctionCallThrowsWhenRendererNotSetForCustomFunction(): void
    {
        $runtime = $this->createRuntime(
            functions: [
                'strtoupper' => new RecordingFunction(),
            ],
        );

        self::expectException(RuntimeException::class);

        $runtime->functionCall('strtoupper');
    }

    public function testFunctionCallInvokesCustomFunctionWithRendererSet(): void
    {
        $function = new RecordingFunction(
            returnValue: 'custom-result',
        );

        $runtime = $this->createRuntime(
            functions: [
                'strtoupper' => $function,
            ],
        );

        $this->attachRenderer($runtime);

        $result = $runtime->functionCall('strtoupper', ['arg']);

        self::assertSame('custom-result', $result);
        self::assertSame(['arg'], $function->lastArguments);
        self::assertNotNull($function->lastContext);
    }

    public function testFunctionCallFallsBackToGlobalFunctionUnderAllowAllPolicy(): void
    {
        $runtime = $this->createRuntime(
            policy: RuntimeFunctionPolicy::ALLOW_ALL,
        );

        self::assertSame('HELLO', $runtime->functionCall('strtoupper', ['hello']));
    }

    public function testInstanceCallReturnsObjectByDefault(): void
    {
        $runtime = $this->createRuntime();
        $object = new PlainObject();

        self::assertSame($object, $runtime->instanceCall($object));
    }

    public function testInstanceCallReturnsNullForNonObjectInNullSafeMode(): void
    {
        $runtime = $this->createRuntime();

        self::assertNull(
            $runtime->instanceCall(null, nullSafe: true),
        );
    }

    public function testInstanceCallThrowsForNonObjectWithoutNullSafe(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->instanceCall('not-an-object');
    }

    public function testInstanceCallThrowsWhenClassNotInAllowList(): void
    {
        $runtime = $this->createRuntime(
            instanceCallClasses: [
                PlainObject::class,
            ],
        );

        self::expectException(RuntimeException::class);

        $runtime->instanceCall(new OtherObject());
    }

    public function testInstanceCallAllowsObjectInAllowList(): void
    {
        $runtime = $this->createRuntime(
            instanceCallClasses: [
                PlainObject::class,
            ],
        );
        $object = new PlainObject();

        self::assertSame($object, $runtime->instanceCall($object));
    }

    public function testInstanceCallThrowsWhenInstanceIsRuntime(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->instanceCall($runtime);
    }

    public function testHasFilterReturnsTrueForRegisteredFilter(): void
    {
        $runtime = $this->createRuntime(
            filters: [
                'upper' => new RecordingFilter(),
            ],
        );

        self::assertTrue($runtime->hasFilter('upper'));
    }

    public function testHasFilterReturnsFalseForUnknownFilter(): void
    {
        $runtime = $this->createRuntime();

        self::assertFalse($runtime->hasFilter('upper'));
    }

    public function testFilterThrowsForUnknownFilter(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->filter('value', 'unknown');
    }

    public function testFilterThrowsWhenRendererNotSet(): void
    {
        $runtime = $this->createRuntime(
            filters: [
                'upper' => new RecordingFilter(),
            ],
        );

        self::expectException(RuntimeException::class);

        $runtime->filter('value', 'upper');
    }

    public function testFilterCallsRegisteredFilter(): void
    {
        $filter = new RecordingFilter(
            returnValue: 'FILTERED',
        );

        $runtime = $this->createRuntime(
            filters: [
                'upper' => $filter,
            ],
        );

        $this->attachRenderer($runtime);

        self::assertSame('FILTERED', $runtime->filter('hello', 'upper'));
        self::assertSame('hello', $filter->lastValue);
        self::assertNotNull($filter->lastContext);
    }

    public function testPropertyAccessReturnsObject(): void
    {
        $runtime = $this->createRuntime();
        $object = new PlainObject();

        self::assertSame($object, $runtime->propertyAccess($object));
    }

    public function testPropertyAccessReturnsNullForNonObjectInNullSafeMode(): void
    {
        $runtime = $this->createRuntime();

        self::assertNull(
            $runtime->propertyAccess(null, nullSafe: true),
        );
    }

    public function testPropertyAccessThrowsForNonObjectWithoutNullSafe(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->propertyAccess('not-an-object');
    }

    public function testPropertyAccessThrowsWhenInstanceIsRuntime(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->propertyAccess($runtime);
    }

    public function testAssertThisDoesNotThrowForUnrelatedValue(): void
    {
        $runtime = $this->createRuntime();

        $runtime->assertThis(new PlainObject());

        $this->expectNotToPerformAssertions();
    }

    public function testAssertThisThrowsWhenValueIsRuntime(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->assertThis($runtime);
    }

    public function testHasBlockReturnsTrueForRegisteredBlock(): void
    {
        $runtime = $this->createRuntime();

        $runtime->block('header', static function (array $scope): void {
        });

        self::assertTrue($runtime->hasBlock('header'));
    }

    public function testHasBlockReturnsFalseForUnregisteredBlock(): void
    {
        $runtime = $this->createRuntime();

        self::assertFalse($runtime->hasBlock('header'));
    }

    public function testExecuteBlockCallsRegisteredClosureWithScope(): void
    {
        $runtime = $this->createRuntime();
        $captured = null;

        $runtime->block(
            'header',
            static function (array $scope) use (&$captured): void {
                $captured = $scope;
            },
        );

        $scope = [
            'title' => 'hello',
        ];

        $runtime->executeBlock('header', $scope);

        self::assertSame(
            [
                'title' => 'hello',
            ],
            $captured,
        );
    }

    public function testExecuteBlockThrowsForUnknownBlock(): void
    {
        $runtime = $this->createRuntime();
        $scope = [];

        self::expectException(RuntimeException::class);

        $runtime->executeBlock('missing', $scope);
    }

    public function testLayoutThrowsWhenRendererNotSet(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->layout('layouts/base');
    }

    public function testLayoutEchoesRendererOutput(): void
    {
        $runtime = $this->createRuntime(
            directives: [
                'lumi.autoescape' => true,
            ],
        );

        $renderer = $this->attachRenderer($runtime);
        $renderer->output = 'rendered-layout';

        \ob_start();
        $runtime->layout('layouts/base', ['title' => 'home']);
        $output = \ob_get_clean();

        self::assertSame('rendered-layout', $output);
        self::assertCount(1, $renderer->renderCalls);
        self::assertSame('layouts/base', $renderer->renderCalls[0]['view']->name);
        self::assertSame(['title' => 'home'], $renderer->renderCalls[0]['view']->scope);
        self::assertSame(['lumi.autoescape' => true], $renderer->renderCalls[0]['directives']);
    }

    public function testHighlightDelegatesToEngine(): void
    {
        $runtime = $this->createRuntime();

        \ob_start();
        $runtime->highlight('lumi-dark', '<source/>');
        $output = \ob_get_clean();

        self::assertSame('<highlighted/>', $output);
        self::assertSame('<source/>', $this->engine->lastHighlightedSource);
        self::assertSame('lumi-dark', $this->engine->lastHighlightedTheme);
        self::assertFalse($this->engine->lastHighlightedOptimized);
    }

    public function testIncludeThrowsForNonStringFile(): void
    {
        $runtime = $this->createRuntime();
        $this->attachRenderer($runtime);

        self::expectException(RuntimeException::class);

        $runtime->include(42);
    }

    public function testIncludeThrowsForFileOutsideViewBaseDirectory(): void
    {
        $runtime = $this->createRuntime();
        $this->attachRenderer($runtime);

        self::expectException(RuntimeException::class);

        $runtime->include('../../../etc/passwd');
    }

    public function testIncludeRendersResolvedViewWithinBaseDirectory(): void
    {
        $runtime = $this->createRuntime(
            directives: [
                'k' => 'v',
            ],
        );
        $renderer = $this->attachRenderer($runtime);

        $name = 'tuxxedo_runtime_include_' . \uniqid('', true);
        $file = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . $name . '.lumi';

        \file_put_contents($file, 'placeholder');

        try {
            \ob_start();
            $runtime->include($name, ['extra' => 1]);
            $output = \ob_get_clean();

            self::assertSame('<rendered/>', $output);
            self::assertCount(1, $renderer->renderCalls);
            self::assertSame($name, $renderer->renderCalls[0]['view']->name);

            self::assertSame(
                [
                    'extra' => 1,
                ],
                $renderer->renderCalls[0]['view']->scope,
            );

            self::assertSame(
                [
                    'k' => 'v',
                ],
                $renderer->renderCalls[0]['directives'],
            );
        } finally {
            @\unlink($file);
        }
    }
}
