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

use Fixture\View\Lumi\Runtime\ContextFirstFilter;
use Fixture\View\Lumi\Runtime\OtherObject;
use Fixture\View\Lumi\Runtime\PlainObject;
use Fixture\View\Lumi\Runtime\RecordingFilter;
use Fixture\View\Lumi\Runtime\RecordingFunction;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Runtime\StubLumiEngine;
use Support\View\Lumi\Runtime\StubRenderer;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Library\Function\PhpFunctionInterface;
use Tuxxedo\View\Lumi\Runtime\Loader;
use Tuxxedo\View\Lumi\Runtime\Runtime;
use Tuxxedo\View\Lumi\Runtime\RuntimeContext;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;
use Tuxxedo\View\Lumi\Runtime\RuntimeFunctionPolicy;
use Tuxxedo\View\Lumi\Runtime\RuntimeInterface;

class RuntimeTest extends TestCase
{
    private StubLumiEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new StubLumiEngine();
    }

    /**
     * @param array<string, string|int|float|bool|null> $directives
     * @param array<string, PhpFunctionInterface> $phpFunctions
     * @param array<class-string> $instanceCallClasses
     * @param array<string, \Closure(mixed[], RuntimeInterface): mixed> $filterDispatchers
     * @param array<string, \Closure(mixed[], RuntimeInterface): mixed> $functionDispatchers
     */
    private function createRuntime(
        RuntimeFunctionPolicy $policy = RuntimeFunctionPolicy::CUSTOM_ONLY,
        array $directives = [],
        array $phpFunctions = [],
        array $instanceCallClasses = [],
        array $filterDispatchers = [],
        array $functionDispatchers = [],
    ): Runtime {
        return new Runtime(
            engine: $this->engine,
            instanceResolver: static fn (string $class): object => new $class(),
            directives: $directives,
            phpFunctions: $phpFunctions,
            functionPolicy: $policy,
            instanceCallClasses: $instanceCallClasses,
            filterDispatchers: $filterDispatchers,
            functionDispatchers: $functionDispatchers,
        );
    }

    /**
     * @return \Closure(mixed[], RuntimeInterface): mixed
     */
    private function makeDispatcher(
        object $handler,
        string $methodName,
        ?int $contextIndex = null,
    ): \Closure {
        return static function (array $arguments, RuntimeInterface $runtime) use ($handler, $methodName, $contextIndex): mixed {
            if ($contextIndex !== null) {
                $arguments = [
                    ...\array_slice($arguments, 0, $contextIndex),
                    new RuntimeContext(
                        runtime: $runtime,
                    ),
                    ...\array_slice($arguments, $contextIndex),
                ];
            }

            /** @var callable $callable */
            $callable = [
                $handler,
                $methodName,
            ];

            return \call_user_func_array($callable, $arguments);
        };
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

    public function testConstructorLowercasesPhpFunctionKeys(): void
    {
        $runtime = $this->createRuntime(
            phpFunctions: [
                'UpperCase' => new PhpFunction(
                    name: 'UpperCase',
                ),
            ],
        );

        self::assertArrayHasKey('uppercase', $runtime->phpFunctions);
    }

    public function testConstructorExposesInstancesTable(): void
    {
        $handler = new RecordingFilter();
        $runtime = new Runtime(
            engine: $this->engine,
            instanceResolver: static fn (string $class): object => new $class(),
            instances: [
                RecordingFilter::class => $handler,
            ],
        );

        self::assertSame($handler, $runtime->instances[RecordingFilter::class]);
    }

    public function testResolveInstanceReturnsCachedInstanceWithoutInvokingResolver(): void
    {
        $handler = new RecordingFilter();
        $invocations = 0;

        $runtime = new Runtime(
            engine: $this->engine,
            instanceResolver: static function (string $class) use (&$invocations): object {
                ++$invocations;

                return new $class();
            },
            instances: [
                RecordingFilter::class => $handler,
            ],
        );

        self::assertSame($handler, $runtime->resolveInstance(RecordingFilter::class));
        self::assertSame(0, $invocations);
    }

    public function testResolveInstanceInvokesResolverAndCachesResult(): void
    {
        $invocations = 0;

        $runtime = new Runtime(
            engine: $this->engine,
            instanceResolver: static function (string $class) use (&$invocations): object {
                ++$invocations;

                return new $class();
            },
        );

        $first = $runtime->resolveInstance(RecordingFilter::class);
        $second = $runtime->resolveInstance(RecordingFilter::class);

        self::assertInstanceOf(RecordingFilter::class, $first);
        self::assertSame($first, $second);
        self::assertSame(1, $invocations);
    }

    public function testConstructorLowercasesDispatcherKeys(): void
    {
        $filter = new RecordingFilter();

        $runtime = $this->createRuntime(
            filterDispatchers: [
                'Recording' => $this->makeDispatcher(
                    handler: $filter,
                    methodName: 'record',
                    contextIndex: 1,
                ),
            ],
        );

        self::assertArrayHasKey('recording', $runtime->filterDispatchers);
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

        $runtime->functionCall(
            'strtoupper',
            [
                'hello',
            ],
        );
    }

    public function testFunctionCallThrowsForUnknownCustomFunctionUnderCustomOnlyPolicy(): void
    {
        $runtime = $this->createRuntime();

        self::expectException(RuntimeException::class);

        $runtime->functionCall(
            'strtoupper',
            [
                'hello',
            ],
        );
    }

    public function testFunctionCallInvokesPhpFunctionWithoutRenderer(): void
    {
        $runtime = $this->createRuntime(
            phpFunctions: [
                'uppercase' => new PhpFunction(
                    name: 'uppercase',
                    mappedName: 'strtoupper',
                ),
            ],
        );

        self::assertSame(
            'HELLO',
            $runtime->functionCall(
                'uppercase',
                [
                    'hello',
                ],
            ),
        );
    }

    public function testFunctionCallResolvesPhpFunctionCaseInsensitively(): void
    {
        $runtime = $this->createRuntime(
            phpFunctions: [
                'uppercase' => new PhpFunction(
                    name: 'uppercase',
                    mappedName: 'strtoupper',
                ),
            ],
        );

        self::assertSame(
            'HELLO',
            $runtime->functionCall(
                'UPPERCASE',
                [
                    'hello',
                ],
            ),
        );
    }

    public function testFunctionCallDispatchesTypedFunction(): void
    {
        $function = new RecordingFunction();
        $function->returnValue = 'typed-result';

        $runtime = $this->createRuntime(
            functionDispatchers: [
                'recording' => $this->makeDispatcher(
                    handler: $function,
                    methodName: 'run',
                    contextIndex: 0,
                ),
            ],
        );
        $this->attachRenderer($runtime);

        self::assertSame(
            'typed-result',
            $runtime->functionCall(
                'recording',
                [
                    'arg',
                ],
            ),
        );

        self::assertSame(
            [
                'arg',
            ],
            $function->lastArguments,
        );
        self::assertNotNull($function->lastContext);
    }

    public function testFunctionCallThrowsWhenRendererNotSetForTypedFunction(): void
    {
        $function = new RecordingFunction();

        $runtime = $this->createRuntime(
            functionDispatchers: [
                'recording' => $this->makeDispatcher(
                    handler: $function,
                    methodName: 'run',
                    contextIndex: 0,
                ),
            ],
        );

        self::expectException(RuntimeException::class);

        $runtime->functionCall('recording');
    }

    public function testFunctionCallFallsBackToGlobalFunctionUnderAllowAllPolicy(): void
    {
        $runtime = $this->createRuntime(
            policy: RuntimeFunctionPolicy::ALLOW_ALL,
        );

        self::assertSame(
            'HELLO',
            $runtime->functionCall(
                'strtoupper',
                [
                    'hello',
                ],
            ),
        );
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

    public function testFilterThrowsForUnknownFilter(): void
    {
        $runtime = $this->createRuntime();
        $this->attachRenderer($runtime);

        self::expectException(RuntimeException::class);

        $runtime->filter('value', 'unknown');
    }

    public function testFilterThrowsWhenRendererNotSet(): void
    {
        $filter = new RecordingFilter();

        $runtime = $this->createRuntime(
            filterDispatchers: [
                'recording' => $this->makeDispatcher(
                    handler: $filter,
                    methodName: 'record',
                    contextIndex: 1,
                ),
            ],
        );

        self::expectException(RuntimeException::class);

        $runtime->filter('value', 'recording');
    }

    public function testFilterCallsRegisteredFilter(): void
    {
        $filter = new RecordingFilter();
        $filter->returnValue = 'FILTERED';

        $runtime = $this->createRuntime(
            filterDispatchers: [
                'recording' => $this->makeDispatcher(
                    handler: $filter,
                    methodName: 'record',
                    contextIndex: 1,
                ),
            ],
        );
        $this->attachRenderer($runtime);

        self::assertSame('FILTERED', $runtime->filter('hello', 'recording'));
        self::assertSame('hello', $filter->lastValue);
        self::assertNotNull($filter->lastContext);
    }

    public function testFilterDispatchesContextFirstFilter(): void
    {
        $filter = new ContextFirstFilter();

        $runtime = $this->createRuntime(
            filterDispatchers: [
                'context_first' => $this->makeDispatcher(
                    handler: $filter,
                    methodName: 'run',
                    contextIndex: 0,
                ),
            ],
        );
        $this->attachRenderer($runtime);

        self::assertSame('hello', $runtime->filter('hello', 'context_first'));
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
        $runtime->layout(
            'layouts/base',
            [
                'title' => 'home',
            ],
        );
        $output = \ob_get_clean();

        self::assertSame('rendered-layout', $output);
        self::assertCount(1, $renderer->renderCalls);
        self::assertSame('layouts/base', $renderer->renderCalls[0]['view']->name);
        self::assertSame(
            [
                'title' => 'home',
            ],
            $renderer->renderCalls[0]['view']->scope,
        );
        self::assertSame(
            [
                'lumi.autoescape' => true,
            ],
            $renderer->renderCalls[0]['directives'],
        );
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
            $runtime->include(
                $name,
                [
                    'extra' => 1,
                ],
            );
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
