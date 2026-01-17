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
    // @todo See if we can get rid of this
    private const array PROTECTED_INTERFACES = [
        DependencyResolverInterface::class,
        DefaultInitializableInterface::class,
    ];

    /**
     * @var array<class-string, Lifecycle>
     */
    private array $registry = [];

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
        Lifecycle $lifecycle,
        ?\Closure $initializer = null,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        $className = \is_object($class) ? $class::class : $class;

        $this->registry[$className] = $lifecycle;

        if (
            $lifecycle === Lifecycle::PERSISTENT &&
            !isset($this->persistentDependencies[$className])
        ) {
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

            $aliases = \array_filter(
                $aliases,
                static fn (string $alias): bool => !\in_array($alias, self::PROTECTED_INTERFACES, true),
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
    public function transient(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        return $this->register(
            class: $class,
            lifecycle: Lifecycle::TRANSIENT,
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
    public function transientLazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        return $this->register(
            class: $class,
            initializer: $initializer,
            lifecycle: Lifecycle::TRANSIENT,
            bindInterfaces: $bindInterfaces,
            bindParent: $bindParent,
        );
    }

    /**
     * @param class-string|object $class
     *
     * @throws ContainerException
     */
    public function persistent(
        string|object $class,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        return $this->register(
            class: $class,
            lifecycle: Lifecycle::PERSISTENT,
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
    public function persistentLazy(
        string $class,
        \Closure $initializer,
        bool $bindInterfaces = true,
        bool $bindParent = true,
    ): static {
        return $this->register(
            class: $class,
            initializer: $initializer,
            lifecycle: Lifecycle::PERSISTENT,
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
        $className = $this->resolveAlias($className);
        $lifecycle = $this->registry[$className] ?? Lifecycle::TRANSIENT;

        if (
            $lifecycle === Lifecycle::PERSISTENT &&
            isset($this->persistentDependencies[$className])
        ) {
            /** @var TClassName */
            return $this->persistentDependencies[$className];
        }

        if (isset($this->initializers[$className])) {
            /** @var TClassName $instance */
            $instance = ($this->initializers[$className])($this);

            if ($lifecycle === Lifecycle::PERSISTENT) {
                unset($this->initializers[$className]);

                $this->persistentDependencies[$className] = $instance;
            }

            return $instance;
        }

        $class = new \ReflectionClass($className);
        $maskedClassName = $className;

        if ($class->isInterface()) {
            $maskedLifecycle = null;
            $class = $this->resolveDefaultImplementation($class, $maskedLifecycle);
            $maskedClassName = $class->name;

            if ($maskedLifecycle !== null) {
                $this->register(
                    class: $className,
                    lifecycle: $maskedLifecycle,
                    bindInterfaces: false,
                    bindParent: false,
                );

                $lifecycle = $maskedLifecycle;
            }
        }

        // @todo Consider lifting this limitation in regards to $arguments here and in other places
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

        if (
            $lifecycle === Lifecycle::PERSISTENT &&
            \array_key_exists($className, $this->persistentDependencies)
        ) {
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
        $className = $this->resolveAlias($className);

        return \array_key_exists($className, $this->registry);
    }

    public function isTransient(
        string $className,
    ): bool {
        $className = $this->resolveAlias($className);

        return \array_key_exists($className, $this->registry) &&
            $this->registry[$className] === Lifecycle::TRANSIENT;
    }

    public function isPersistent(
        string $className,
    ): bool {
        $className = $this->resolveAlias($className);

        return \array_key_exists($className, $this->registry) &&
            $this->registry[$className] === Lifecycle::PERSISTENT;
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
        return $this->isAlias($alias) &&
            $this->aliases[$alias] === $className;
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
        ?Lifecycle &$lifecycle = null,
    ): \ReflectionClass {
        $attributes = $interface->getAttributes(
            name: DefaultImplementation::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            /** @var DefaultImplementation $implementation */
            $implementation = $attributes[0]->newInstance();
            $lifecycle = $implementation->lifecycle ?? $this->resolveDefaultLifecycle($interface);

            return new \ReflectionClass($implementation->class);
        }

        return $interface;
    }

    /**
     * @param \ReflectionClass<object> $interface
     */
    private function resolveDefaultLifecycle(
        \ReflectionClass $interface,
    ): ?Lifecycle {
        $attributes = $interface->getAttributes(
            name: DefaultLifecycle::class,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) > 0) {
            return $attributes[0]->newInstance()->lifecycle;
        }

        return null;
    }

    /**
     * @param class-string $className
     * @return class-string
     */
    private function resolveAlias(
        string $className,
    ): string {
        while (\array_key_exists($className, $this->aliases)) {
            $className = $this->aliases[$className];
        }

        return $className;
    }
}
