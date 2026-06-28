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

namespace Tuxxedo\Config;

use Tuxxedo\Collection\FileCollection;
use Tuxxedo\Config\Attribute\ConfigKey;
use Tuxxedo\Config\Attribute\ConfigNamespace;
use Tuxxedo\Container\ContainerInterface;

class Config implements ConfigInterface
{
    /**
     * @param array<mixed> $directives
     */
    final public function __construct(
        #[\SensitiveParameter] protected array $directives = [],
    ) {
    }

    public static function createFromDirectory(
        ContainerInterface $container,
        string $directory,
    ): static {
        $directives = [];
        $typedConfigs = [];
        $deferredArrayClosures = [];

        foreach (FileCollection::fromFileType($directory, '.php') as $configFile) {
            $index = \str_replace($directory . '/', '', \substr($configFile, 0, -4));

            static::processFileEntry(
                container: $container,
                file: $configFile,
                derivedKey: $index,
                directives: $directives,
                typedConfigs: $typedConfigs,
                deferredArrayClosures: $deferredArrayClosures,
            );
        }

        static::finalizeLoader(
            container: $container,
            directives: $directives,
            typedConfigs: $typedConfigs,
            deferredArrayClosures: $deferredArrayClosures,
        );

        return new static(
            directives: $directives,
        );
    }

    public static function createFromFile(
        ContainerInterface $container,
        string $file,
    ): static {
        $directives = [];
        $typedConfigs = [];
        $deferredArrayClosures = [];

        static::processFileEntry(
            container: $container,
            file: $file,
            derivedKey: null,
            directives: $directives,
            typedConfigs: $typedConfigs,
            deferredArrayClosures: $deferredArrayClosures,
        );

        static::finalizeLoader(
            container: $container,
            directives: $directives,
            typedConfigs: $typedConfigs,
            deferredArrayClosures: $deferredArrayClosures,
        );

        return new static(
            directives: $directives,
        );
    }

    /**
     * @param array<mixed> $directives
     * @param array<class-string, string> $typedConfigs
     * @param list<array{closure: \Closure, derivedKey: ?string}> $deferredArrayClosures
     */
    protected static function processFileEntry(
        ContainerInterface $container,
        string $file,
        ?string $derivedKey,
        array &$directives,
        array &$typedConfigs,
        array &$deferredArrayClosures,
    ): void {
        $entry = (static function (string $configFileName): mixed {
            return require $configFileName;
        })($file);

        if ($entry instanceof \Closure) {
            /** @var \Closure(): object $entry */

            $returnType = self::reflectClosureReturnType($entry);

            if ($returnType !== null) {
                if (!\interface_exists($returnType) && !\class_exists($returnType)) {
                    // @codeCoverageIgnoreStart
                    throw ConfigException::fromInvalidTypedConfigReturnType(
                        file: $file,
                        returnType: $returnType,
                    );
                    // @codeCoverageIgnoreEnd
                }

                if (\array_key_exists($returnType, $typedConfigs)) {
                    throw ConfigException::fromDuplicateConfigNamespace(
                        namespace: $returnType,
                    );
                }

                $container->singletonLazy(
                    $returnType,
                    static fn (ContainerInterface $container, array $arguments): object => $container->call($entry),
                );

                $typedConfigs[$returnType] = $file;

                return;
            }

            $deferredArrayClosures[] = [
                'closure' => $entry,
                'derivedKey' => $derivedKey,
            ];

            return;
        }

        if (!\is_array($entry)) {
            $entry = (array) $entry;
        }

        self::storeAtDerivedKey(
            directives: $directives,
            entry: $entry,
            derivedKey: $derivedKey,
        );
    }

    private static function reflectClosureReturnType(
        \Closure $closure,
    ): ?string {
        $reflection = new \ReflectionFunction($closure);
        $returnType = $reflection->getReturnType();

        if (!$returnType instanceof \ReflectionNamedType) {
            return null;
        }

        if ($returnType->isBuiltin()) {
            return null;
        }

        return $returnType->getName();
    }

    /**
     * @param array<mixed> $directives
     * @param array<mixed> $entry
     */
    private static function storeAtDerivedKey(
        array &$directives,
        array $entry,
        ?string $derivedKey,
    ): void {
        if ($derivedKey === null) {
            foreach ($entry as $key => $value) {
                $directives[$key] = $value;
            }

            return;
        }

        $directives[$derivedKey] = $entry;
    }

    /**
     * @param array<mixed> $directives
     * @param array<class-string, string> $typedConfigs
     * @param list<array{closure: \Closure, derivedKey: ?string}> $deferredArrayClosures
     */
    protected static function finalizeLoader(
        ContainerInterface $container,
        array &$directives,
        array $typedConfigs,
        array $deferredArrayClosures,
    ): void {
        foreach (\array_keys($typedConfigs) as $interfaceName) {
            self::flattenTypedConfig(
                container: $container,
                directives: $directives,
                interfaceName: $interfaceName,
            );
        }

        foreach ($deferredArrayClosures as $deferred) {
            /** @var \Closure(): mixed $closure */
            $closure = $deferred['closure'];
            $result = $container->call($closure);

            if (!\is_array($result)) {
                $result = (array) $result;
            }

            self::storeAtDerivedKey(
                directives: $directives,
                entry: $result,
                derivedKey: $deferred['derivedKey'],
            );
        }
    }

    /**
     * @param array<mixed> $directives
     * @param class-string $interfaceName
     */
    private static function flattenTypedConfig(
        ContainerInterface $container,
        array &$directives,
        string $interfaceName,
    ): void {
        $object = $container->resolve($interfaceName);
        $class = new \ReflectionClass($object);
        $namespace = self::readConfigNamespace($class);

        if ($namespace === null) {
            return;
        }

        $values = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $leafKey = self::readConfigKey($property) ?? $property->getName();

            if (\array_key_exists($leafKey, $values)) {
                throw ConfigException::fromDuplicateConfigKey(
                    path: $namespace . '.' . $leafKey,
                );
            }

            $values[$leafKey] = $property->getValue($object);
        }

        $parts = \explode('.', $namespace);
        $leaf = \array_pop($parts);
        $ref = &$directives;

        foreach ($parts as $part) {
            /** @var array<mixed> $ref */
            if (\array_key_exists($part, $ref) && !\is_array($ref[$part])) {
                throw ConfigException::fromDuplicateConfigNamespace(
                    namespace: $namespace,
                );
            }

            /** @var array<mixed> $ref */
            $ref[$part] ??= [];
            $ref = &$ref[$part];
        }

        /** @var array<mixed> $ref */
        if (\array_key_exists($leaf, $ref)) {
            throw ConfigException::fromDuplicateConfigNamespace(
                namespace: $namespace,
            );
        }

        $ref[$leaf] = $values;

        unset($ref);
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private static function readConfigNamespace(
        \ReflectionClass $class,
    ): ?string {
        $attributes = $class->getAttributes(ConfigNamespace::class);

        if (\sizeof($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance()->namespace;
    }

    private static function readConfigKey(
        \ReflectionProperty $property,
    ): ?string {
        $attributes = $property->getAttributes(ConfigKey::class);

        if (\sizeof($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance()->name;
    }

    public function has(
        string $path,
    ): bool {
        try {
            $this->path($path);
        } catch (ConfigException) {
            return false;
        }

        return true;
    }

    public function path(
        string $path,
    ): mixed {
        $index = $this->directives;

        if (\str_contains($path, '.')) {
            foreach (\explode('.', $path) as $part) {
                if (!\array_key_exists($part, $index)) {
                    throw ConfigException::fromInvalidDirective(
                        directive: $path,
                    );
                }

                if (!\is_array($index[$part])) {
                    return $index[$part];
                }

                $index = $index[$part];
            }
        } elseif (\array_key_exists($path, $index)) {
            return $index[$path];
        }

        if ($index === $this->directives) {
            throw ConfigException::fromInvalidDirective(
                directive: $path,
            );
        }

        return $index;
    }
}
