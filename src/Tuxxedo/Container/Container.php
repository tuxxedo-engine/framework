<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Container;

class Container
{
    /**
     * @var array<class-string, object|null>
     */
    protected array $persistentDependencies = [];

    /**
     * @var array<class-string, class-string>
     */
    protected array $aliases = [];

    /**
     * @var array<class-string, mixed[]>
     */
    protected array $resolvedArguments = [];

    /**
     * @param class-string|object $class
     */
    public function persistent(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        $className = \is_object($class) ? $class::class : $class;

        if (!isset($this->persistentDependencies[$className])) {
            $this->persistentDependencies[$className] = \is_object($class) ? $class : null;
        }

        $aliases = [];

        if ($bindInterfaces) {
            $aliases = ($aliases = \class_implements($class)) !== false ? $aliases : [];
        }

        if ($bindParent && ($parentClassName = \get_parent_class($class)) !== false) {
            $aliases[] = $parentClassName;
        }

        $this->alias($aliases, \is_object($class) ? $class::class : $class);

        return $this;
    }

    /**
     * @param class-string|class-string[] $aliasClassName
     * @param class-string $resolvedClassName
     */
    public function alias(
        string|array $aliasClassName,
        string $resolvedClassName,
    ): static {
        if (\is_string($aliasClassName)) {
            $aliasClassName = [
                $aliasClassName,
            ];
        }

        foreach ($aliasClassName as $alias) {
            $this->aliases[$alias] = $resolvedClassName;
        }

        return $this;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $className
     * @return TClassName
     *
     * @throws UnresolvableDependencyException
     */
    public function resolve(string $className): object
    {
        if (\array_key_exists($className, $this->aliases)) {
            $className = $this->aliases[$className];
        }

        if (isset($this->persistentDependencies[$className])) {
            /** @var TClassName */
            return $this->persistentDependencies[$className];
        }

        if (!\array_key_exists($className, $this->resolvedArguments)) {
            $arguments = [];
            $class = new \ReflectionClass($className);

            if ($class->implementsInterface(AlwaysPersistentInterface::class)) {
                $this->persistent($className);
            }

            if (($ctor = $class->getConstructor()) !== null) {
                foreach ($ctor->getParameters() as $parameter) {
                    $arguments[$parameter->getName()] = $this->resolveParameter($parameter);
                }
            }

            $this->resolvedArguments[$className] = $arguments;
        }

        $instance = new $className(
            ...$this->resolvedArguments[$className],
        );

        if (\array_key_exists($className, $this->persistentDependencies)) {
            $this->persistentDependencies[$className] = $instance;
        }

        /** @var TClassName */
        return $instance;
    }

    /**
     * @throws UnresolvableDependencyException
     */
    protected function resolveParameter(\ReflectionParameter $parameter): mixed
    {
        $attrs = $parameter->getAttributes(
            name: DependencyResolverInterface::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attrs) > 0) {
            try {
                return $attrs[0]->newInstance()->resolve($this);
            } catch (\Exception $e) {
                throw UnresolvableDependencyException::fromAttributeException(
                    attributeClass: $attrs[0]->getName(),
                    exception: $e,
                );
            }
        }

        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->hasType()) {
            $type = $parameter->getType();

            return match (true) {
                $type instanceof \ReflectionNamedType => $this->resolveNamedType($type),
                $type instanceof \ReflectionUnionType => $this->resolveUnionType($type),
                default => throw UnresolvableDependencyException::fromIntersectionType(),
            };
        }

        throw UnresolvableDependencyException::fromUnresolvableType();
    }

    /**
     * @throws UnresolvableDependencyException
     */
    protected function resolveNamedType(\ReflectionNamedType $type): mixed
    {
        if (!$type->isBuiltin()) {
            try {
                /** @var class-string $className */
                $className = $type->getName();

                return $this->resolve($className);
            } catch(\Exception) {
                if ($type->allowsNull()) {
                    return null;
                }
            }
        }

        throw UnresolvableDependencyException::fromNamedType($type);
    }

    /**
     * @throws UnresolvableDependencyException
     */
    protected function resolveUnionType(\ReflectionUnionType $unionType): mixed
    {
        foreach ($unionType->getTypes() as $type) {
            if ($type instanceof \ReflectionNamedType) {
                try {
                    return $this->resolveNamedType($type);
                } catch (\Exception) {
                }
            }
        }

        throw UnresolvableDependencyException::fromUnionType($unionType);
    }
}
