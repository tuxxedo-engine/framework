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

use Tuxxedo\Cache\Driver\CacheDriverInterface;
use Tuxxedo\Mapper\MapperInterface;
use Tuxxedo\Reflection\EnumHydrator;
use Tuxxedo\Temporal\DurationInterface;

class Cache implements CacheInterface
{
    private const int MAX_KEY_LENGTH = 255;

    /**
     * @param list<class-string> $allowedClasses
     */
    public function __construct(
        public readonly CacheDriverInterface $driver,
        public readonly MapperInterface $mapper,
        public readonly string $prefix = '',
        public readonly array $allowedClasses = [],
    ) {
    }

    /**
     * @throws CacheException
     */
    private function key(
        string $key,
    ): string {
        $prefixedKey = $this->prefix . $key;

        if (
            $key === '' ||
            \strlen($prefixedKey) > self::MAX_KEY_LENGTH ||
            \preg_match('/[\x00-\x1F\x7F]/', $prefixedKey) === 1
        ) {
            throw CacheException::fromInvalidKey($prefixedKey);
        }

        return $prefixedKey;
    }

    /**
     * @throws CacheException
     */
    private function assertTtl(
        string $key,
        ?DurationInterface $ttl,
    ): void {
        if ($ttl !== null && !$ttl->isPositive()) {
            throw CacheException::fromInvalidTtl($key);
        }
    }

    /**
     * @return int|float|string|bool|array<mixed>|object|null
     *
     * @throws CacheException
     */
    private function read(
        string $key,
    ): int|float|string|bool|array|object|null {
        $value = $this->driver->fetch(
            key: $this->key($key),
            allowedClasses: $this->allowedClasses,
        );

        $this->assertAllowedObjects($key, $value);

        return $value;
    }

    /**
     * @throws CacheException
     */
    private function assertAllowedObjects(
        string $key,
        mixed $value,
    ): void {
        if (\is_array($value)) {
            foreach ($value as $item) {
                $this->assertAllowedObjects($key, $item);
            }

            return;
        }

        if (\is_object($value) && !\in_array($value::class, $this->allowedClasses, true)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'an allowed class',
                actual: $value::class,
            );
        }
    }

    /**
     * @return array<mixed>|null
     *
     * @throws CacheException
     */
    private function readArray(
        string $key,
    ): ?array {
        $value = $this->read($key);

        if ($value === null) {
            return null;
        }

        if (!\is_array($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'array',
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    /**
     * @return int|float|string|bool|array<mixed>|object
     *
     * @throws CacheException
     */
    private function encode(
        string $key,
        mixed $value,
    ): int|float|string|bool|array|object {
        if (\is_array($value)) {
            $encoded = [];

            foreach ($value as $index => $item) {
                $encoded[$index] = $this->encode($key, $item);
            }

            return $encoded;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_object($value)) {
            if (!\in_array($value::class, $this->allowedClasses, true)) {
                throw CacheException::fromUnsupportedValue(
                    key: $key,
                    type: $value::class,
                );
            }

            return $value;
        }

        if (
            \is_int($value) ||
            \is_float($value) ||
            \is_string($value) ||
            \is_bool($value)
        ) {
            return $value;
        }

        throw CacheException::fromUnsupportedValue(
            key: $key,
            type: \get_debug_type($value),
        );
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum&\UnitEnum
     *
     * @throws CacheException
     */
    private function hydrateEnum(
        string $key,
        string $enum,
        mixed $value,
    ): \UnitEnum {
        $case = \is_int($value) || \is_string($value)
            ? EnumHydrator::hydrate($enum, (string) $value)
            : null;

        if (!$case instanceof $enum) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: $enum,
                actual: \get_debug_type($value),
            );
        }

        return $case;
    }

    /**
     * @throws CacheException
     */
    private function assertEnum(
        string $key,
        string $enum,
    ): void {
        if (!\enum_exists($enum)) {
            // @codeCoverageIgnoreStart
            throw CacheException::fromInvalidEnum(
                key: $key,
                enum: $enum,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param array<mixed> $values
     * @param \Closure(mixed): bool $predicate
     *
     * @throws CacheException
     */
    private function assertElements(
        string $key,
        array $values,
        string $type,
        \Closure $predicate,
    ): void {
        foreach ($values as $value) {
            if (!$predicate($value)) {
                throw CacheException::fromTypeMismatch(
                    key: $key,
                    expected: 'array<' . $type . '>',
                    actual: 'array<' . \get_debug_type($value) . '>',
                );
            }
        }
    }

    #[\NoDiscard]
    public function has(
        string $key,
    ): bool {
        return $this->driver->exists($this->key($key));
    }

    public function string(
        string $key,
    ): ?string {
        $value = $this->read($key);

        if ($value !== null && !\is_string($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'string',
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function int(
        string $key,
    ): ?int {
        $value = $this->read($key);

        if ($value !== null && !\is_int($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'int',
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function float(
        string $key,
    ): ?float {
        $value = $this->read($key);

        if ($value !== null && !\is_float($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'float',
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function bool(
        string $key,
    ): ?bool {
        $value = $this->read($key);

        if ($value !== null && !\is_bool($value)) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: 'bool',
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function enum(
        string $key,
        string $enum,
    ): ?object {
        $this->assertEnum($key, $enum);

        $value = $this->read($key);

        if ($value === null) {
            return null;
        }

        return $this->hydrateEnum(
            key: $key,
            enum: $enum,
            value: $value,
        );
    }

    public function arrayOfString(
        string $key,
    ): ?array {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        $this->assertElements(
            key: $key,
            values: $values,
            type: 'string',
            predicate: \is_string(...),
        );

        /** @var array<array-key, string> $values */
        return $values;
    }

    public function arrayOfInt(
        string $key,
    ): ?array {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        $this->assertElements(
            key: $key,
            values: $values,
            type: 'int',
            predicate: \is_int(...),
        );

        /** @var array<array-key, int> $values */
        return $values;
    }

    public function arrayOfFloat(
        string $key,
    ): ?array {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        $this->assertElements(
            key: $key,
            values: $values,
            type: 'float',
            predicate: \is_float(...),
        );

        /** @var array<array-key, float> $values */
        return $values;
    }

    public function arrayOfBool(
        string $key,
    ): ?array {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        $this->assertElements(
            key: $key,
            values: $values,
            type: 'bool',
            predicate: \is_bool(...),
        );

        /** @var array<array-key, bool> $values */
        return $values;
    }

    public function arrayOfEnum(
        string $key,
        string $enum,
    ): ?array {
        $this->assertEnum($key, $enum);

        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        $cases = [];

        foreach ($values as $index => $value) {
            $cases[$index] = $this->hydrateEnum(
                key: $key,
                enum: $enum,
                value: $value,
            );
        }

        return $cases;
    }

    public function object(
        string $key,
        string $class,
    ): ?object {
        $value = $this->read($key);

        if ($value === null) {
            return null;
        }

        if (!$value instanceof $class) {
            throw CacheException::fromTypeMismatch(
                key: $key,
                expected: $class,
                actual: \get_debug_type($value),
            );
        }

        return $value;
    }

    public function mapTo(
        string $key,
        string|object $className,
    ): ?object {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        return $this->mapper->mapArrayTo($values, $className);
    }

    public function mapToArrayOf(
        string $key,
        string|object $className,
    ): ?array {
        $values = $this->readArray($key);

        if ($values === null) {
            return null;
        }

        return $this->mapper->mapToArrayOf($values, $className);
    }

    public function set(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl = null,
    ): void {
        $this->assertTtl($key, $ttl);

        $this->driver->store(
            key: $this->key($key),
            value: $this->encode($key, $value),
            ttl: $ttl,
        );
    }

    #[\NoDiscard]
    public function add(
        string $key,
        int|float|string|bool|array|object $value,
        ?DurationInterface $ttl = null,
    ): bool {
        $this->assertTtl($key, $ttl);

        return $this->driver->add(
            key: $this->key($key),
            value: $this->encode($key, $value),
            ttl: $ttl,
        );
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
        $value = $this->read($key);

        if ($value !== null) {
            /** @var T $value */
            return $value;
        }

        $this->assertTtl($key, $ttl);

        $value = $factory();
        $encoded = $this->encode($key, $value);

        if ($encoded !== $value) {
            throw CacheException::fromUnsupportedValue(
                key: $key,
                type: 'enum',
            );
        }

        $this->driver->store(
            key: $this->key($key),
            value: $encoded,
            ttl: $ttl,
        );

        return $value;
    }

    public function increment(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int {
        if ($by <= 0) {
            throw CacheException::fromInvalidStep(
                key: $key,
                by: $by,
            );
        }

        $this->assertTtl($key, $ttl);

        return $this->driver->increment(
            key: $this->key($key),
            by: $by,
            ttl: $ttl,
        );
    }

    public function decrement(
        string $key,
        int $by = 1,
        ?DurationInterface $ttl = null,
    ): int {
        if ($by <= 0) {
            throw CacheException::fromInvalidStep(
                key: $key,
                by: $by,
            );
        }

        $this->assertTtl($key, $ttl);

        return $this->driver->decrement(
            key: $this->key($key),
            by: $by,
            ttl: $ttl,
        );
    }

    public function delete(
        string $key,
    ): void {
        $this->driver->remove($this->key($key));
    }

    public function clear(): void
    {
        $this->driver->clear($this->prefix);
    }
}
