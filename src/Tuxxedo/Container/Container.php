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
        DefaultInitializableInterface::class,
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
     * @var array<class-string, (\Closure(self): object)>
     */
    private array $initializers = [];

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName> $class
     * @param (\Closure(self): TClassName)|null $initializer
     *
     * @throws ContainerException
     */
    private function register(
        string|object $class,
        ?\Closure $initializer = null,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        $className = \is_object($class) ? $class::class : $class;

        if (!isset($this->persistentDependencies[$className])) {
            $this->persistentDependencies[$className] = \is_object($class) ? $class : null;
        }

        if ($bindInterfaces) {
            $aliases = ($aliases = \class_implements($class)) !== false ? $aliases : [];

            if (
                $initializer === null &&
                \is_string($class) &&
                \in_array(DefaultInitializableInterface::class, $aliases, true)
            ) {
                /** @var class-string<TClassName&DefaultInitializableInterface> $class */
                $this->initializers[$className] = static fn (self $container): object => $class::createInstance($container);
            } elseif ($initializer !== null) {
                $this->initializers[$className] = $initializer;
            }

            $aliases = $this->filterInterfaces(
                interfaces: $aliases,
            );
        } else {
            if ($initializer !== null) {
                $this->initializers[$className] = $initializer;
            }

            $aliases = [];
        }

        if ($bindParent && ($parentClassName = \get_parent_class($class)) !== false) {
            $aliases[] = $parentClassName;
        }

        $this->alias($aliases, $className);

        return $this;
    }

    /**
     * @param class-string|object $class
     *
     * @throws ContainerException
     */
    public function bind(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        return $this->register(
            class: $class,
            bindInterfaces: $bindInterfaces,
            bindParent: $bindParent,
        );
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
        return $this->register(
            class: $class,
            initializer: $initializer,
            bindInterfaces: $bindInterfaces,
            bindParent: $bindParent,
        );
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
        array $arguments = [],
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

        $class = new \ReflectionClass($className);
        $maskedClassName = $className;

        if ($class->isInterface()) {
            $class = $this->resolveDefaultImplementation($class);
            $maskedClassName = $class->name;
        }

        if ($class->implementsInterface(AlwaysPersistentInterface::class)) {
            $this->bind($className);
        }

        if (
            \sizeof($arguments) === 0 &&
            $class->implementsInterface(DefaultInitializableInterface::class)
        ) {
            /** @var TClassName $instance */
            $instance = $maskedClassName::createInstance($this);
        } else {
            $callArguments = [];

            if (($ctor = $class->getConstructor()) !== null) {
                foreach ($ctor->getParameters() as $parameter) {
                    $callArguments[$parameter->getName()] = \array_key_exists($parameter->getName(), $arguments)
                        ? $arguments[$parameter->getName()]
                        : (
                            \array_key_exists($parameter->getPosition(), $arguments)
                            ? $arguments[$parameter->getPosition()]
                            : $this->resolveParameter($parameter)
                        );
                }
            }

            $instance = new $maskedClassName(
                ...$callArguments,
            );
        }

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
        return \array_key_exists($className, $this->persistentDependencies);
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
            } catch (\Throwable $e) {
                throw ContainerException::fromAttributeException(
                    attributeClass: $attrs[0]->getName(),
                    exception: $e,
                );
            }
        }

        try {
            if ($parameter->hasType()) {
                $type = $parameter->getType();

                return match (true) {
                    $type instanceof \ReflectionNamedType => $this->resolveNamedType($type),
                    $type instanceof \ReflectionUnionType => $this->resolveUnionType($type),
                    $type instanceof \ReflectionIntersectionType => throw ContainerException::fromIntersectionType(
                        intersectionType: $type,
                    ),
                    default => ContainerException::fromUnresolvableType(),
                };
            }

            throw ContainerException::fromUnresolvableType();
        } catch (ContainerException $exception) {
            if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw $exception;
        }
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
            } catch (\Throwable $exception) {
                if ($type->allowsNull()) {
                    return null;
                }

                throw ContainerException::fromException(
                    exception: $exception,
                );
            }
        }

        throw ContainerException::fromNamedType(
            type: $type,
        );
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

        throw ContainerException::fromUnionType(
            unionType: $unionType,
        );
    }

    /**
     * @param \ReflectionClass<object> $interface
     * @return \ReflectionClass<object>
     */
    private function resolveDefaultImplementation(
        \ReflectionClass $interface,
    ): \ReflectionClass {
        $attributes = $interface->getAttributes(
            name: DefaultImplementation::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            return new \ReflectionClass($attributes[0]->newInstance()->class);
        }

        return $interface;
    }
}
