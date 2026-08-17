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

namespace Tuxxedo\Validator;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Model\Attribute\Relation\RelationInterface;
use Tuxxedo\Reflection\ClassReflector;
use Tuxxedo\Reflection\PropertyReflector;

/**
 * @todo Related entities reached through relation-attributed properties (`#[BelongsTo]`, `#[HasOne]`, `#[HasMany]`, `#[HasManyThrough]`, `#[HasOneThrough]`, `#[BelongsToMany]`) are NOT validated during the parent's save. Reading the relation property triggers Engine's lazy-proxy hydrator and causes runaway recursion via bidirectional links, so those properties are skipped entirely — no value read, no rules invoked, no cascade. Each related entity is expected to run its own validation via its own `ModelsManager::save()`. Revisit when a real "validate the whole aggregate in one shot" use case surfaces — likely wants an explicit `#[Valid]` opt-in marker plus identity-map awareness to break cycles without disabling the walk. See discussion in the Validator design doc.
 */
class Validator implements ValidatorInterface
{
    public const int MAX_RECURSION_DEPTH = 32;

    /**
     * @var array<class-string, list<PropertyMetaData>>
     */
    private array $metaDataCache = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function validate(
        object $target,
        ?string $group = null,
    ): ValidationResult {
        $violations = [];

        /** @var \WeakMap<object, true> $visited */
        $visited = new \WeakMap();

        $this->walkObject(
            target: $target,
            path: '',
            rootObject: $target,
            group: $group,
            depth: 0,
            violations: $violations,
            visited: $visited,
        );

        return new ValidationResult(
            isValid: \sizeof($violations) === 0,
            violations: $violations,
        );
    }

    public function validateOrThrow(
        object $target,
        ?string $group = null,
    ): void {
        $result = $this->validate(
            target: $target,
            group: $group,
        );

        if ($result->isValid) {
            return;
        }

        throw new ValidationException(
            violations: $result->violations,
        );
    }

    /**
     * @param list<ViolationInterface> $violations
     * @param \WeakMap<object, true> $visited
     *
     * @throws ValidatorException
     */
    private function walkObject(
        object $target,
        string $path,
        object $rootObject,
        ?string $group,
        int $depth,
        array &$violations,
        \WeakMap $visited,
    ): void {
        if (isset($visited[$target])) {
            return;
        }

        $visited[$target] = true;

        if ($depth > self::MAX_RECURSION_DEPTH) {
            throw ValidatorException::fromRecursionDepthExceeded(
                path: $path,
                limit: self::MAX_RECURSION_DEPTH,
            );
        }

        foreach ($this->metaDataFor($target::class) as $property) {
            if ($property->skipCascade) {
                continue;
            }

            $propertyPath = $path === ''
                ? $property->name
                : $path . '.' . $property->name;

            $reflector = PropertyReflector::createFromObject(
                object: $target,
                property: $property->name,
            );

            $value = $reflector->getValue($target);

            $context = new ValidationContext(
                currentPath: $propertyPath,
                rootObject: $rootObject,
                group: $group,
            );

            foreach ($property->rules as $rule) {
                $violation = $rule->check(
                    value: $value,
                    context: $context,
                    container: $this->container,
                );

                if ($violation !== null) {
                    $violations[] = $violation;
                }
            }

            $this->cascade(
                value: $value,
                path: $propertyPath,
                rootObject: $rootObject,
                group: $group,
                depth: $depth + 1,
                violations: $violations,
                visited: $visited,
            );
        }
    }

    /**
     * @param list<ViolationInterface> $violations
     * @param \WeakMap<object, true> $visited
     *
     * @throws ValidatorException
     */
    private function cascade(
        mixed $value,
        string $path,
        object $rootObject,
        ?string $group,
        int $depth,
        array &$violations,
        \WeakMap $visited,
    ): void {
        if (\is_object($value)) {
            $this->walkObject(
                target: $value,
                path: $path,
                rootObject: $rootObject,
                group: $group,
                depth: $depth,
                violations: $violations,
                visited: $visited,
            );

            return;
        }

        if (!\is_array($value)) {
            return;
        }

        foreach ($value as $key => $element) {
            if (!\is_object($element)) {
                continue;
            }

            $this->walkObject(
                target: $element,
                path: $path . '.' . (string) $key,
                rootObject: $rootObject,
                group: $group,
                depth: $depth,
                violations: $violations,
                visited: $visited,
            );
        }
    }

    /**
     * @param class-string $className
     * @return list<PropertyMetaData>
     */
    private function metaDataFor(
        string $className,
    ): array {
        if (isset($this->metaDataCache[$className])) {
            return $this->metaDataCache[$className];
        }

        $metaData = [];
        $classReflector = new ClassReflector(
            reflector: new \ReflectionClass($className),
        );

        foreach ($classReflector->properties() as $property) {
            if ($property->reflector->isStatic()) {
                continue;
            }

            $rules = [];

            foreach ($property->getAttributes(RuleProviderInterface::class) as $provider) {
                foreach ($provider->toRules() as $rule) {
                    $rules[] = $rule;
                }
            }

            foreach ($property->getAttributes(RuleInterface::class) as $rule) {
                $rules[] = $rule;
            }

            $skipCascade = $property->hasAttribute(RelationInterface::class);

            $metaData[] = new PropertyMetaData(
                name: $property->name,
                rules: $rules,
                skipCascade: $skipCascade,
            );
        }

        return $this->metaDataCache[$className] = $metaData;
    }
}
