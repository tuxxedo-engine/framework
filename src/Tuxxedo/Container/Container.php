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

class Container implements ContainerInterface
{
    private const array PROTECTED_INTERFACES = [
        AlwaysPersistentInterface::class,
        DependencyResolverInterface::class,
        LazyInitializableInterface::class,
    ];

    /**
     * @var array<class-string, object|null>
     */
    private array $persistentDependencies = [];

    /**
     * @var array<class-string, class-string>
     */
    private array $aliases = [];

    /**
     * @var array<class-string, mixed[]>
     */
    private array $resolvedArguments = [];

    /**
     * @var array<class-string, (\Closure(self): object)>
     */
    private array $initializers = [];

    /**
     * @param class-string|object $class
     */
    public function bind(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        $className = \is_object($class) ? $class::class : $class;

        if (!isset($this->persistentDependencies[$className])) {
            $this->persistentDependencies[$className] = \is_object($class) ? $class : null;
        }

        if ($bindInterfaces) {
            $aliases = ($aliases = \class_implements($class)) !== false ? $aliases : [];

            if (\is_string($class) && \in_array(LazyInitializableInterface::class, $aliases, true)) {
                /** @var class-string<LazyInitializableInterface> $class */
                $this->initializers[$className] = static fn (self $container): object => $class::createInstance($container);
            }

            $aliases = $this->filterInterfaces(
                interfaces: $aliases,
            );
        } else {
            $aliases = [];
        }

        if ($bindParent && ($parentClassName = \get_parent_class($class)) !== false) {
            $aliases[] = $parentClassName;
        }

        $this->alias($aliases, $className);

        return $this;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @param (\Closure(self): TClassName) $initializer
     *
     * @throws ContainerException
     */
    public function lazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        if (\is_subclass_of($class, LazyInitializableInterface::class)) {
            throw ContainerException::fromAmbiguousInitializer();
        }

        $this->bind(
            class: $class,
            bindInterfaces: $bindInterfaces,
            bindParent: $bindParent,
        );

        $this->initializers[$class] = $initializer;

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
     * @throws ContainerException
     */
    public function resolve(
        string $className,
    ): object {
        if (\array_key_exists($className, $this->aliases)) {
            $className = $this->aliases[$className];
        }

        if (isset($this->persistentDependencies[$className])) {
            /** @var TClassName */
            return $this->persistentDependencies[$className];
        }

        if (isset($this->initializers[$className])) {
            /** @var TClassName $instance */
            $instance = ($this->initializers[$className])($this);

            if ($instance instanceof AlwaysPersistentInterface) {
                unset($this->initializers[$className]);

                $this->persistentDependencies[$className] = $instance;
            }

            return $instance;
        }

        if (!\array_key_exists($className, $this->resolvedArguments)) {
            $arguments = [];
            $class = new \ReflectionClass($className);

            if ($class->implementsInterface(AlwaysPersistentInterface::class)) {
                $this->bind($className);
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

    public function call(
        \Closure $callable,
        array $arguments = [],
    ): mixed {
        $callArguments = [];

        foreach ((new \ReflectionFunction($callable))->getParameters() as $parameter) {
            $callArguments[$parameter->getName()] = \array_key_exists($parameter->getName(), $arguments)
                ? $arguments[$parameter->getName()]
                : (
                    \array_key_exists($parameter->getPosition(), $arguments)
                        ? $arguments[$parameter->getPosition()]
                        : $this->resolveParameter($parameter)
                );
        }

        return $callable(...$callArguments);
    }

    public function isBound(
        string $className,
    ): bool {
        return isset($this->persistentDependencies[$className]);
    }

    public function isInitialized(
        string $className,
    ): bool {
        return $this->isBound($className) && !isset($this->initializers[$className]);
    }

    public function isAlias(
        string $className,
    ): bool {
        return \array_key_exists($className, $this->aliases);
    }

    public function isAliasOf(
        string $alias,
        string $className,
    ): bool {
        return $this->isAlias($alias) && $this->aliases[$alias] === $className;
    }

    /**
     * @param class-string[] $interfaces
     * @return class-string[]
     */
    private function filterInterfaces(
        array $interfaces,
    ): array {
        return \array_filter(
            $interfaces,
            static fn (string $interface): bool => !\in_array($interface, self::PROTECTED_INTERFACES, true),
        );
    }

    /**
     * @throws ContainerException
     */
    private function resolveParameter(
        \ReflectionParameter $parameter,
    ): mixed {
        $attrs = $parameter->getAttributes(
            name: DependencyResolverInterface::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attrs) > 0) {
            try {
                return $attrs[0]->newInstance()->resolve($this);
            } catch (\Exception $e) {
                throw ContainerException::fromAttributeException(
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
                default => throw ContainerException::fromIntersectionType(),
            };
        }

        throw ContainerException::fromUnresolvableType();
    }

    /**
     * @throws ContainerException
     */
    private function resolveNamedType(
        \ReflectionNamedType $type,
    ): mixed {
        if (!$type->isBuiltin()) {
            try {
                /** @var class-string $className */
                $className = $type->getName();

                return $this->resolve($className);
            } catch (\Exception $exception) {
                if ($type->allowsNull()) {
                    return null;
                }

                throw $exception;
            }
        }

        throw ContainerException::fromNamedType($type);
    }

    /**
     * @throws ContainerException
     */
    private function resolveUnionType(
        \ReflectionUnionType $unionType,
    ): mixed {
        foreach ($unionType->getTypes() as $type) {
            if ($type instanceof \ReflectionNamedType) {
                try {
                    return $this->resolveNamedType($type);
                } catch (\Exception) {
                }
            }
        }

        throw ContainerException::fromUnionType($unionType);
    }
}
