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

namespace Tuxxedo\Cache;

use Tuxxedo\Cache\Config\CacheConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Temporal\DurationInterface;

class CacheManager implements CacheManagerInterface
{
    /**
     * @param array<array-key, CacheInterface> $scopes
     * @param list<array-key>|null $isolatedScopes
     */
    public function __construct(
        public readonly CacheInterface $root,
        public readonly array $scopes = [],
        public readonly ?string $separator = ':',
        public readonly ?array $isolatedScopes = null,
    ) {
    }

    /**
     * @throws CacheException
     */
    public static function createFromConfig(
        ContainerInterface $container,
        CacheConfigInterface $config,
    ): self {
        $mapper = $container->resolve(MapperInterface::class);
        $defaultDriver = ($config->default->driverClass)::create($container, $config->default);
        $scopes = [];
        $isolatedScopes = [];

        foreach ($config->scopes as $scope) {
            if ($scope->driver !== null) {
                $isolatedScopes[] = $scope->name;
            }

            $scopes[$scope->name] = new Cache(
                driver: $scope->driver !== null
                    ? ($scope->driver->driverClass)::create($container, $scope->driver)
                    : $defaultDriver,
                mapper: $mapper,
                prefix: $scope->name . ($config->separator ?? ''),
            );
        }

        return new self(
            root: new Cache(
                driver: $defaultDriver,
                mapper: $mapper,
            ),
            scopes: $scopes,
            separator: $config->separator,
            isolatedScopes: $isolatedScopes,
        );
    }

    /**
     * @return array{CacheInterface, string}
     *
     * @throws CacheException
     */
    private function route(
        string $key,
    ): array {
        if ($this->separator !== null) {
            $position = \strpos($key, $this->separator);

            if ($position === false) {
                return [
                    $this->root,
                    $key,
                ];
            }

            return [
                $this->scope(\substr($key, 0, $position)),
                \substr($key, $position + \strlen($this->separator)),
            ];
        }

        foreach (\array_keys($this->scopes) as $name) {
            $name = (string) $name;

            if (\str_starts_with($key, $name) && \strlen($key) > \strlen($name)) {
                return [
                    $this->scopes[$name],
                    \substr($key, \strlen($name)),
                ];
            }
        }

        return [
            $this->root,
            $key,
        ];
    }

    public function scope(
        string $name,
    ): CacheInterface {
        return $this->scopes[$name] ?? throw CacheException::fromUnknownScope($name);
    }

    #[\NoDiscard]
    public function has(
        string $key,
    ): bool {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->has($scopedKey);
    }

    public function string(
        string $key,
    ): ?string {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->string($scopedKey);
    }

    public function int(
        string $key,
    ): ?int {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->int($scopedKey);
    }

    public function float(
        string $key,
    ): ?float {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->float($scopedKey);
    }

    public function bool(
        string $key,
    ): ?bool {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->bool($scopedKey);
    }

    public function enum(
        string $key,
        string $enum,
    ): ?object {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->enum($scopedKey, $enum);
    }

    public function arrayOfString(
        string $key,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->arrayOfString($scopedKey);
    }

    public function arrayOfInt(
        string $key,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->arrayOfInt($scopedKey);
    }

    public function arrayOfFloat(
        string $key,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->arrayOfFloat($scopedKey);
    }

    public function arrayOfBool(
        string $key,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->arrayOfBool($scopedKey);
    }

    public function arrayOfEnum(
        string $key,
        string $enum,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->arrayOfEnum($scopedKey, $enum);
    }

    public function object(
        string $key,
        string $class,
    ): ?object {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->object($scopedKey, $class);
    }

    public function mapTo(
        string $key,
        string|object $className,
    ): ?object {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->mapTo($scopedKey, $className);
    }

    public function mapToArrayOf(
        string $key,
        string|object $className,
    ): ?array {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->mapToArrayOf($scopedKey, $className);
    }

    public function set(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl = null,
    ): void {
        [$cache, $scopedKey] = $this->route($key);

        $cache->set($scopedKey, $value, $ttl);
    }

    #[\NoDiscard]
    public function add(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl = null,
    ): bool {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->add($scopedKey, $value, $ttl);
    }

    /**
     * @template T of int|float|string|bool|array<mixed>|object
     *
     * @param \Closure(): T $factory
     * @return T
     */
    public function remember(
        string $key,
        \Closure $factory,
        ?DurationInterface $ttl = null,
    ): int|float|string|bool|array|object {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->remember($scopedKey, $factory, $ttl);
    }

    public function increment(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->increment($scopedKey, $by, $ttl);
    }

    public function decrement(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int {
        [$cache, $scopedKey] = $this->route($key);

        return $cache->decrement($scopedKey, $by, $ttl);
    }

    public function delete(
        string $key,
    ): void {
        [$cache, $scopedKey] = $this->route($key);

        $cache->delete($scopedKey);
    }

    public function clear(): void
    {
        foreach ($this->scopes as $name => $scope) {
            if (
                $this->isolatedScopes === null ||
                \in_array($name, $this->isolatedScopes, true)
            ) {
                $scope->clear();
            }
        }

        $this->root->clear();
    }
}
