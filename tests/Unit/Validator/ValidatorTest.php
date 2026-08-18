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

namespace Unit\Validator;

use Fixture\Validator\ArrayContainerDto;
use Fixture\Validator\AssertAfterFailedRulesDto;
use Fixture\Validator\AssertBasicDto;
use Fixture\Validator\AssertContextDto;
use Fixture\Validator\AssertMultipleDto;
use Fixture\Validator\AssertNestedChild;
use Fixture\Validator\AssertNestedParent;
use Fixture\Validator\AssertServiceDto;
use Fixture\Validator\ChildDto;
use Fixture\Validator\ContainerAwareDto;
use Fixture\Validator\ContainerAwareRuleContext;
use Fixture\Validator\ContextSpyDto;
use Fixture\Validator\ContextSpyRuleContext;
use Fixture\Validator\DeepNode;
use Fixture\Validator\FixtureViolationCode;
use Fixture\Validator\MultipleRulesPerPropertyDto;
use Fixture\Validator\NoRulesDto;
use Fixture\Validator\ParentDto;
use Fixture\Validator\ParityChecker;
use Fixture\Validator\ParityCheckerInterface;
use Fixture\Validator\SingleFailingDto;
use Fixture\Validator\StaticPropertyDto;
use Fixture\Validator\TwoPropertiesDto;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;
use Tuxxedo\Validator\ValidatorException;

class ValidatorTest extends TestCase
{
    private function makeValidator(
        ?Container $container = null,
    ): Validator {
        return new Validator(
            container: $container ?? new Container(),
        );
    }

    public function testValidateReturnsValidResultForClassWithNoRules(): void
    {
        $result = $this->makeValidator()->validate(
            target: new NoRulesDto(
                name: 'anything',
            ),
        );

        self::assertTrue($result->isValid);
        self::assertSame([], $result->violations);
    }

    public function testValidateReturnsViolationFromSingleRule(): void
    {
        $result = $this->makeValidator()->validate(
            target: new SingleFailingDto(
                name: 'nope',
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(1, \sizeof($result->violations));
        self::assertSame(FixtureViolationCode::ALWAYS_FAIL, $result->violations[0]->code);
        self::assertSame('name', $result->violations[0]->propertyPath);
        self::assertSame('nope', $result->violations[0]->invalidValue);
    }

    public function testValidateCollectsMultipleViolationsFromSameProperty(): void
    {
        $result = $this->makeValidator()->validate(
            target: new MultipleRulesPerPropertyDto(
                name: 'anything',
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(2, \sizeof($result->violations));
        self::assertSame('name', $result->violations[0]->propertyPath);
        self::assertSame('name', $result->violations[1]->propertyPath);
    }

    public function testValidateWalksMultiplePropertiesInOrder(): void
    {
        $result = $this->makeValidator()->validate(
            target: new TwoPropertiesDto(
                first: 'a',
                second: 'b',
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(1, \sizeof($result->violations));
        self::assertSame('first', $result->violations[0]->propertyPath);
    }

    public function testValidateOrThrowReturnsQuietlyWhenValid(): void
    {
        self::expectNotToPerformAssertions();

        $this->makeValidator()->validateOrThrow(
            target: new NoRulesDto(
                name: 'ok',
            ),
        );
    }

    public function testValidateOrThrowThrowsValidationExceptionWhenInvalid(): void
    {
        self::expectException(ValidationException::class);

        $this->makeValidator()->validateOrThrow(
            target: new SingleFailingDto(
                name: 'nope',
            ),
        );
    }

    public function testValidateOrThrowPreservesViolationsOnException(): void
    {
        try {
            $this->makeValidator()->validateOrThrow(
                target: new SingleFailingDto(
                    name: 'nope',
                ),
            );

            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(1, \sizeof($e->violations));
            self::assertSame(FixtureViolationCode::ALWAYS_FAIL, $e->violations[0]->code);
        }
    }

    public function testValidationExceptionReportsUnprocessableEntity(): void
    {
        try {
            $this->makeValidator()->validateOrThrow(
                target: new SingleFailingDto(
                    name: 'nope',
                ),
            );

            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertNotNull($e->responseCode);
            self::assertSame(422, $e->responseCode->value);
        }
    }

    public function testValidateSkipsStaticProperties(): void
    {
        $result = $this->makeValidator()->validate(
            target: new StaticPropertyDto(
                instanceField: 'value',
            ),
        );

        self::assertSame(1, \sizeof($result->violations));
        self::assertSame('instanceField', $result->violations[0]->propertyPath);
    }

    public function testValidateCachesMetadataAcrossCalls(): void
    {
        $validator = $this->makeValidator();

        $first = $validator->validate(
            target: new SingleFailingDto(
                name: 'one',
            ),
        );

        $second = $validator->validate(
            target: new SingleFailingDto(
                name: 'two',
            ),
        );

        self::assertSame(1, \sizeof($first->violations));
        self::assertSame(1, \sizeof($second->violations));
        self::assertSame('one', $first->violations[0]->invalidValue);
        self::assertSame('two', $second->violations[0]->invalidValue);
    }

    public function testValidateCascadesIntoNestedObject(): void
    {
        $result = $this->makeValidator()->validate(
            target: new ParentDto(
                parentField: 'nope',
                child: new ChildDto(
                    childField: 'also nope',
                ),
            ),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'parentField',
                'child.childField',
            ],
            $paths,
        );
    }

    public function testValidateCascadesIntoArrayElementsThatAreObjects(): void
    {
        $result = $this->makeValidator()->validate(
            target: new ArrayContainerDto(
                items: [
                    new ChildDto(
                        childField: 'a',
                    ),
                    new ChildDto(
                        childField: 'b',
                    ),
                ],
            ),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'items.0.childField',
                'items.1.childField',
            ],
            $paths,
        );
    }

    public function testValidateCascadeSkipsScalarArrayElements(): void
    {
        $result = $this->makeValidator()->validate(
            target: new ArrayContainerDto(
                items: [
                    'scalar',
                    new ChildDto(
                        childField: 'child',
                    ),
                    'another-scalar',
                ],
            ),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'items.1.childField',
            ],
            $paths,
        );
    }

    public function testValidateBreaksCyclesViaVisitedSet(): void
    {
        $node = new DeepNode(
            label: 'self',
        );

        $node->child = $node;

        $result = $this->makeValidator()->validate(
            target: $node,
        );

        self::assertTrue($result->isValid);
    }

    public function testValidateThrowsOnRecursionDepthExceeded(): void
    {
        $leaf = new DeepNode(
            label: 'leaf',
        );

        $node = $leaf;

        for ($i = 0; $i < Validator::MAX_RECURSION_DEPTH + 2; $i++) {
            $wrapper = new DeepNode(
                label: 'level_' . (string) $i,
            );

            $wrapper->child = $node;
            $node = $wrapper;
        }

        try {
            $this->makeValidator()->validate(
                target: $node,
            );

            self::fail('Expected ValidatorException');
        } catch (ValidatorException $e) {
            self::assertStringContainsString('recursion depth', $e->getMessage());
        }
    }

    public function testValidateAutowiresServicesIntoRuleCheck(): void
    {
        $container = new Container();
        $container->singleton(ParityChecker::class);
        $container->alias(ParityCheckerInterface::class, ParityChecker::class);

        $result = $this->makeValidator(
            container: $container,
        )->validate(
            target: new ContainerAwareDto(
                number: 3,
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(FixtureViolationCode::ODD_NUMBER, $result->violations[0]->code);
        self::assertInstanceOf(ContainerAwareRuleContext::class, $result->violations[0]->context);
        self::assertSame(3, $result->violations[0]->context->received);
    }

    public function testValidateAcceptsWhenAutowiredRulePasses(): void
    {
        $container = new Container();
        $container->singleton(ParityChecker::class);
        $container->alias(ParityCheckerInterface::class, ParityChecker::class);

        $result = $this->makeValidator(
            container: $container,
        )->validate(
            target: new ContainerAwareDto(
                number: 4,
            ),
        );

        self::assertTrue($result->isValid);
    }

    public function testValidateExposesRootObjectInContext(): void
    {
        $target = new ContextSpyDto(
            probe: 'anything',
        );

        $result = $this->makeValidator()->validate(
            target: $target,
        );

        self::assertSame(1, \sizeof($result->violations));
        self::assertInstanceOf(ContextSpyRuleContext::class, $result->violations[0]->context);
        self::assertSame(
            ContextSpyDto::class,
            $result->violations[0]->context->rootClass,
        );
    }

    public function testValidateExposesGroupInContext(): void
    {
        $result = $this->makeValidator()->validate(
            target: new ContextSpyDto(
                probe: 'anything',
            ),
            group: 'create',
        );

        self::assertInstanceOf(ContextSpyRuleContext::class, $result->violations[0]->context);
        self::assertSame('create', $result->violations[0]->context->group);
    }

    public function testValidateEmitsEmptyGroupWhenNoneProvided(): void
    {
        $result = $this->makeValidator()->validate(
            target: new ContextSpyDto(
                probe: 'anything',
            ),
        );

        self::assertInstanceOf(ContextSpyRuleContext::class, $result->violations[0]->context);
        self::assertSame('', $result->violations[0]->context->group);
    }

    public function testValidateInvokesAssertMethodWhenFieldsMismatch(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertBasicDto(
                password: 'a',
                passwordConfirmation: 'b',
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(1, \sizeof($result->violations));
        self::assertSame('passwordConfirmation', $result->violations[0]->propertyPath);
    }

    public function testValidateAssertMethodProducesNoViolationOnPass(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertBasicDto(
                password: 'same',
                passwordConfirmation: 'same',
            ),
        );

        self::assertTrue($result->isValid);
    }

    public function testValidateAssertMethodReceivesContextViaAttribute(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertContextDto(),
        );

        self::assertSame(1, \sizeof($result->violations));
        self::assertSame('label', $result->violations[0]->propertyPath);
    }

    public function testValidateAssertMethodAutowiresContainerServices(): void
    {
        $container = new Container();
        $container->singleton(ParityChecker::class);
        $container->alias(ParityCheckerInterface::class, ParityChecker::class);

        $result = $this->makeValidator(
            container: $container,
        )->validate(
            target: new AssertServiceDto(
                number: 5,
            ),
        );

        self::assertFalse($result->isValid);
        self::assertSame(FixtureViolationCode::ODD_NUMBER, $result->violations[0]->code);
        self::assertInstanceOf(ContainerAwareRuleContext::class, $result->violations[0]->context);
        self::assertSame(5, $result->violations[0]->context->received);
    }

    public function testValidateAssertMethodPassesWhenAutowiredCheckPasses(): void
    {
        $container = new Container();
        $container->singleton(ParityChecker::class);
        $container->alias(ParityCheckerInterface::class, ParityChecker::class);

        $result = $this->makeValidator(
            container: $container,
        )->validate(
            target: new AssertServiceDto(
                number: 4,
            ),
        );

        self::assertTrue($result->isValid);
    }

    public function testValidateCollectsMultipleAssertMethodsInDeclarationOrder(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertMultipleDto(),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'first',
                'second-a',
                'second-b',
            ],
            $paths,
        );
    }

    public function testValidateCascadesIntoNestedAssertMethods(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertNestedParent(
                child: new AssertNestedChild(),
            ),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'child.child-field',
                'parent-field',
            ],
            $paths,
        );
    }

    public function testValidateRunsAssertMethodEvenAfterSingleFieldFailure(): void
    {
        $result = $this->makeValidator()->validate(
            target: new AssertAfterFailedRulesDto(),
        );

        $paths = \array_map(
            static fn ($v) => $v->propertyPath,
            $result->violations,
        );

        self::assertSame(
            [
                'field',
                'assert-emitted',
            ],
            $paths,
        );
    }
}
