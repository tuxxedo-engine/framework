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

use Tuxxedo\Serializer\MetaData\MetaDataReader;
use Tuxxedo\Serializer\MetaData\MetaDataReaderInterface;
use Tuxxedo\Validator\CommonViolationCode;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;
use Tuxxedo\Validator\ValidatorInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;
use Tuxxedo\Validator\WrongTypeViolationContext;

abstract class AbstractSerializer
{
    protected readonly MetaDataReaderInterface $metaData;
    private readonly ObjectMapper $mapper;

    /**
     * @throws SerializerException
     */
    public function __construct(
        private readonly ?ValidatorInterface $validator = null,
        bool $skipUnknownKeys = true,
        private readonly bool $skipValidation = false,
        ?MetaDataReaderInterface $metaData = null,
    ) {
        if (!$skipValidation && $validator === null) {
            throw SerializerException::fromValidatorRequired(static::class);
        }

        $this->metaData = $metaData ?? new MetaDataReader();
        $this->mapper = new ObjectMapper(
            metaData: $this->metaData,
            skipUnknownKeys: $skipUnknownKeys,
        );
    }

    abstract protected function casting(): Casting;

    /**
     * @template TClassName of object
     *
     * @param array<mixed> $data
     * @param class-string<TClassName> $className
     * @return TClassName
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    protected function hydrateObject(
        array $data,
        string $className,
    ): object {
        $violations = [];
        $object = $this->mapper->hydrate(
            data: $data,
            className: $className,
            casting: $this->casting(),
            path: '',
            violations: $violations,
        );

        if ($object === null) {
            throw new ValidationException($violations);
        }

        $this->validate(
            object: $object,
            prefix: '',
            violations: $violations,
        );

        if (\sizeof($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $object;
    }

    /**
     * @template TClassName of object
     *
     * @param list<mixed> $data
     * @param class-string<TClassName> $className
     * @return list<TClassName>
     *
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    protected function hydrateList(
        array $data,
        string $className,
    ): array {
        $violations = [];
        $objects = [];

        foreach ($data as $index => $item) {
            if (!\is_array($item)) {
                $violations[] = new Violation(
                    code: CommonViolationCode::WRONG_TYPE,
                    propertyPath: (string) $index,
                    invalidValue: $item,
                    context: new WrongTypeViolationContext(
                        expected: $className,
                        received: \get_debug_type($item),
                    ),
                );

                continue;
            }

            $object = $this->mapper->hydrate(
                data: $item,
                className: $className,
                casting: $this->casting(),
                path: (string) $index,
                violations: $violations,
            );

            if ($object !== null) {
                $objects[$index] = $object;
            }
        }

        if (\sizeof($violations) > 0) {
            throw new ValidationException($violations);
        }

        foreach ($objects as $index => $object) {
            $this->validate(
                object: $object,
                prefix: (string) $index,
                violations: $violations,
            );
        }

        if (\sizeof($violations) > 0) {
            throw new ValidationException($violations);
        }

        return \array_values($objects);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SerializerException
     */
    protected function extractObject(
        object $value,
    ): array {
        return $this->mapper->extract($value);
    }

    /**
     * @param iterable<object> $values
     * @return list<array<string, mixed>>
     *
     * @throws SerializerException
     */
    protected function extractList(
        iterable $values,
    ): array {
        $data = [];

        foreach ($values as $value) {
            $data[] = $this->mapper->extract($value);
        }

        return $data;
    }

    /**
     * @param list<ViolationInterface> $violations
     *
     * @throws ValidatorException
     */
    private function validate(
        object $object,
        string $prefix,
        array &$violations,
    ): void {
        if ($this->skipValidation || $this->validator === null) {
            return;
        }

        foreach ($this->validator->validate($object)->violations as $violation) {
            if ($prefix === '') {
                $violations[] = $violation;

                continue;
            }

            $violations[] = new Violation(
                code: $violation->code,
                propertyPath: $violation->propertyPath === ''
                    ? $prefix
                    : $prefix . '.' . $violation->propertyPath,
                invalidValue: $violation->invalidValue,
                context: $violation->context,
            );
        }
    }
}
