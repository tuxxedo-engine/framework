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

namespace Tuxxedo\Serializer;

use Tuxxedo\Serializer\Converter\EnumConverter;
use Tuxxedo\Serializer\Converter\TemporalConverter;
use Tuxxedo\Serializer\MetaData\ClassMetaData;
use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Serializer\MetaData\PropertyMetaData;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Temporal\LocalDateInterface;
use Tuxxedo\Temporal\LocalTimeInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

class ObjectMapper
{
    private const array BUILTIN_TYPES = [
        'array',
        'bool',
        'float',
        'int',
        'mixed',
        'object',
        'string',
    ];

    private const array TRUE_VALUES = [
        '1',
        'true',
        'on',
        'yes',
    ];

    private const array FALSE_VALUES = [
        '0',
        'false',
        'off',
        'no',
        '',
    ];

    private readonly EnumConverter $enumConverter;
    private readonly TemporalConverter $temporalConverter;

    public function __construct(
        private readonly MetaDataReaderInterface $metaData,
        private readonly bool $skipUnknownKeys,
    ) {
        $this->enumConverter = new EnumConverter();
        $this->temporalConverter = new TemporalConverter();
    }

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $data
     * @param class-string<TClassName> $className
     * @param list<ViolationInterface> $violations
     * @return TClassName|null
     *
     * @throws SerializerException
     */
    public function hydrate(
        array $data,
        string $className,
        Casting $casting,
        string $path,
        array &$violations,
    ): ?object {
        $metaData = $this->metaData->read($className);
        $hasConstructor = $metaData->reflection->getConstructor() !== null;
        $before = \sizeof($violations);
        $values = [];
        $known = [];

        foreach ($metaData->properties as $property) {
            $known[$property->key] = true;

            if ($property->ignore) {
                continue;
            }

            $propertyPath = self::path($path, $property->name);

            if (!\array_key_exists($property->key, $data)) {
                if ($property->hasDefault || (!$property->constructorParameter && $hasConstructor)) {
                    continue;
                }

                if ($property->nullable) {
                    $values[$property->name] = null;

                    continue;
                }

                $violations[] = new Violation(
                    code: SerializerViolationCode::MISSING_KEY,
                    propertyPath: $propertyPath,
                    invalidValue: null,
                );

                continue;
            }

            $propertyBefore = \sizeof($violations);
            $value = $this->convertProperty(
                value: $data[$property->key],
                property: $property,
                className: $className,
                path: $propertyPath,
                casting: $casting,
                violations: $violations,
            );

            if (\sizeof($violations) === $propertyBefore) {
                $values[$property->name] = $value;
            }
        }

        if (!$this->skipUnknownKeys) {
            foreach ($data as $key => $value) {
                if (!isset($known[$key])) {
                    $violations[] = new Violation(
                        code: SerializerViolationCode::UNKNOWN_KEY,
                        propertyPath: self::path($path, (string) $key),
                        invalidValue: $value,
                    );
                }
            }
        }

        if (\sizeof($violations) > $before) {
            return null;
        }

        /** @var TClassName */
        return $this->instantiate($metaData, $values);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SerializerException
     */
    public function extract(
        object $value,
    ): array {
        /** @var \WeakMap<object, true> $visited */
        $visited = new \WeakMap();

        return $this->extractObject($value, $visited);
    }

    /**
     * @param class-string $className
     * @param list<ViolationInterface> $violations
     *
     * @throws SerializerException
     */
    private function convertProperty(
        mixed $value,
        PropertyMetaData $property,
        string $className,
        string $path,
        Casting $casting,
        array &$violations,
    ): mixed {
        if ($value === null) {
            if (!$property->nullable) {
                $violations[] = new Violation(
                    code: CommonViolationCode::NULL_VALUE,
                    propertyPath: $path,
                    invalidValue: null,
                );
            }

            return null;
        }

        if ($property->converter !== null) {
            return $this->callConverter(
                converter: $property->converter,
                value: $value,
                context: new PropertyContext(
                    className: $className,
                    propertyName: $property->name,
                    path: $path,
                    typeName: $property->typeName,
                    nullable: $property->nullable,
                    casting: $casting,
                ),
                violations: $violations,
            );
        }

        $elementType = $property->listOf ?? $property->mapOf;

        if ($elementType !== null) {
            if (!\is_array($value)) {
                self::wrongType(
                    path: $path,
                    value: $value,
                    expected: $property->listOf !== null
                        ? 'list'
                        : 'map',
                    violations: $violations,
                );

                return null;
            }

            $converted = [];

            foreach ($value as $key => $element) {
                $elementPath = self::path($path, (string) $key);

                if ($element === null) {
                    $violations[] = new Violation(
                        code: CommonViolationCode::NULL_VALUE,
                        propertyPath: $elementPath,
                        invalidValue: null,
                    );

                    continue;
                }

                $converted[$key] = $this->convertValue(
                    value: $element,
                    type: $elementType,
                    builtin: \in_array($elementType, self::BUILTIN_TYPES, true),
                    className: $className,
                    propertyName: $property->name,
                    path: $elementPath,
                    casting: $casting,
                    violations: $violations,
                );
            }

            return $property->listOf !== null
                ? \array_values($converted)
                : $converted;
        }

        return $this->convertValue(
            value: $value,
            type: $property->typeName,
            builtin: $property->builtin,
            className: $className,
            propertyName: $property->name,
            path: $path,
            casting: $casting,
            violations: $violations,
        );
    }

    /**
     * @param class-string $className
     * @param list<ViolationInterface> $violations
     *
     * @throws SerializerException
     */
    private function convertValue(
        mixed $value,
        ?string $type,
        bool $builtin,
        string $className,
        string $propertyName,
        string $path,
        Casting $casting,
        array &$violations,
    ): mixed {
        if ($type === null || $type === 'mixed') {
            return $value;
        }

        if ($builtin) {
            return $this->convertBuiltin(
                value: $value,
                type: $type,
                path: $path,
                casting: $casting,
                violations: $violations,
            );
        }

        $context = new PropertyContext(
            className: $className,
            propertyName: $propertyName,
            path: $path,
            typeName: $type,
            nullable: false,
            casting: $casting,
        );

        if (\is_a($type, \UnitEnum::class, true)) {
            return $this->callConverter(
                converter: $this->enumConverter,
                value: $value,
                context: $context,
                violations: $violations,
            );
        }

        if (
            \is_a($type, InstantInterface::class, true) ||
            \is_a($type, LocalDateInterface::class, true) ||
            \is_a($type, LocalTimeInterface::class, true)
        ) {
            return $this->callConverter(
                converter: $this->temporalConverter,
                value: $value,
                context: $context,
                violations: $violations,
            );
        }

        if ($value instanceof $type) {
            return $value;
        }

        if (\is_array($value) && \class_exists($type)) {
            return $this->hydrate(
                data: $value,
                className: $type,
                casting: $casting,
                path: $path,
                violations: $violations,
            );
        }

        self::wrongType(
            path: $path,
            value: $value,
            expected: $type,
            violations: $violations,
        );

        return null;
    }

    /**
     * @param list<ViolationInterface> $violations
     */
    private function convertBuiltin(
        mixed $value,
        string $type,
        string $path,
        Casting $casting,
        array &$violations,
    ): mixed {
        $lenient = $casting === Casting::LENIENT;

        $converted = match ($type) {
            'int' => self::castInt($value, $lenient),
            'float' => self::castFloat($value, $lenient),
            'string' => self::castString($value, $lenient),
            'bool' => self::castBool($value, $lenient),
            'array' => \is_array($value)
                ? $value
                : null,
            'object' => \is_object($value)
                ? $value
                : null,
            default => $value,
        };

        if ($converted === null) {
            self::wrongType(
                path: $path,
                value: $value,
                expected: $type,
                violations: $violations,
            );
        }

        return $converted;
    }

    /**
     * @param list<ViolationInterface> $violations
     */
    private function callConverter(
        ValueConverterInterface $converter,
        mixed $value,
        PropertyContextInterface $context,
        array &$violations,
    ): mixed {
        try {
            return $converter->decode($value, $context);
        } catch (SerializerException) {
            $violations[] = new Violation(
                code: SerializerViolationCode::INVALID_VALUE,
                propertyPath: $context->path,
                invalidValue: $value,
            );

            return null;
        }
    }

    /**
     * @param array<string, mixed> $values
     *
     * @throws SerializerException
     */
    private function instantiate(
        ClassMetaData $metaData,
        array $values,
    ): object {
        $reflection = $metaData->reflection;
        $arguments = [];

        foreach ($metaData->properties as $property) {
            if ($property->constructorParameter && \array_key_exists($property->name, $values)) {
                $arguments[$property->name] = $values[$property->name];
            }
        }

        try {
            $object = $reflection->getConstructor() === null
                ? $reflection->newInstance()
                : $reflection->newInstanceArgs($arguments);

            foreach ($metaData->properties as $property) {
                if (!$property->constructorParameter && \array_key_exists($property->name, $values)) {
                    $reflection->getProperty($property->name)->setValue($object, $values[$property->name]);
                }
            }
        } catch (\Throwable $exception) {
            throw SerializerException::fromInstantiationFailure(
                className: $reflection->name,
                previous: $exception,
            );
        }

        return $object;
    }

    /**
     * @param \WeakMap<object, true> $visited
     * @return array<string, mixed>
     *
     * @throws SerializerException
     */
    private function extractObject(
        object $value,
        \WeakMap $visited,
    ): array {
        if (isset($visited[$value])) {
            throw SerializerException::fromCircularReference($value::class);
        }

        $visited[$value] = true;
        $metaData = $this->metaData->read($value::class);
        $data = [];

        foreach ($metaData->properties as $property) {
            if (!$property->readable || $property->ignore) {
                continue;
            }

            $reflectionProperty = $metaData->reflection->getProperty($property->name);

            if (!$reflectionProperty->isInitialized($value)) {
                continue;
            }

            $raw = $reflectionProperty->getValue($value);

            if ($property->converter !== null && $raw !== null) {
                $data[$property->key] = $property->converter->encode(
                    $raw,
                    new PropertyContext(
                        className: $value::class,
                        propertyName: $property->name,
                        path: $property->name,
                        typeName: $property->typeName,
                        nullable: $property->nullable,
                        casting: Casting::STRICT,
                    ),
                );

                continue;
            }

            $data[$property->key] = $this->encodeValue($raw, $visited);
        }

        unset($visited[$value]);

        return $data;
    }

    /**
     * @param \WeakMap<object, true> $visited
     *
     * @throws SerializerException
     */
    private function encodeValue(
        mixed $value,
        \WeakMap $visited,
    ): mixed {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (
            $value instanceof InstantInterface ||
            $value instanceof LocalDateInterface ||
            $value instanceof LocalTimeInterface
        ) {
            return $value->toIso8601();
        }

        if (\is_object($value)) {
            return $this->extractObject($value, $visited);
        }

        if (\is_array($value)) {
            $encoded = [];

            foreach ($value as $key => $element) {
                $encoded[$key] = $this->encodeValue($element, $visited);
            }

            return $encoded;
        }

        return $value;
    }

    private static function castInt(
        mixed $value,
        bool $lenient,
    ): ?int {
        if (\is_int($value)) {
            return $value;
        }

        if ($lenient && \is_string($value)) {
            return \filter_var($value, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    private static function castFloat(
        mixed $value,
        bool $lenient,
    ): ?float {
        if (\is_float($value) || \is_int($value)) {
            return (float) $value;
        }

        if ($lenient && \is_string($value) && \is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    private static function castString(
        mixed $value,
        bool $lenient,
    ): ?string {
        if (\is_string($value)) {
            return $value;
        }

        if ($lenient && (\is_int($value) || \is_float($value))) {
            return (string) $value;
        }

        return null;
    }

    private static function castBool(
        mixed $value,
        bool $lenient,
    ): ?bool {
        if (\is_bool($value)) {
            return $value;
        }

        if ($lenient && (\is_string($value) || \is_int($value))) {
            return self::toBool($value);
        }

        return null;
    }

    private static function toBool(
        string|int $value,
    ): ?bool {
        $normalized = \strtolower((string) $value);

        if (\in_array($normalized, self::TRUE_VALUES, true)) {
            return true;
        }

        if (\in_array($normalized, self::FALSE_VALUES, true)) {
            return false;
        }

        return null;
    }

    /**
     * @param list<ViolationInterface> $violations
     */
    private static function wrongType(
        string $path,
        mixed $value,
        string $expected,
        array &$violations,
    ): void {
        $violations[] = new Violation(
            code: CommonViolationCode::WRONG_TYPE,
            propertyPath: $path,
            invalidValue: $value,
            context: new WrongTypeViolationContext(
                expected: $expected,
                received: \get_debug_type($value),
            ),
        );
    }

    private static function path(
        string $parent,
        string $segment,
    ): string {
        return $parent === ''
            ? $segment
            : $parent . '.' . $segment;
    }
}
