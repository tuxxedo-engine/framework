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

namespace Tuxxedo\Serializer\MetaData;

use Tuxxedo\Serializer\Attribute\CaseStyle;
use Tuxxedo\Serializer\Attribute\Convert;
use Tuxxedo\Serializer\Attribute\Ignore;
use Tuxxedo\Serializer\Attribute\ListOf;
use Tuxxedo\Serializer\Attribute\MapOf;
use Tuxxedo\Serializer\Attribute\Name;
use Tuxxedo\Serializer\Attribute\XmlAttribute;
use Tuxxedo\Serializer\Attribute\XmlList;
use Tuxxedo\Serializer\Attribute\XmlRoot;
use Tuxxedo\Serializer\Attribute\XmlText;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\ValueConverterInterface;
use Tuxxedo\Text\KeyCase;

class MetaDataReader implements MetaDataReaderInterface
{
    /**
     * @var array<class-string, ClassMetaData>
     */
    private array $cache = [];

    public function read(
        string $className,
    ): ClassMetaData {
        if (isset($this->cache[$className])) {
            return $this->cache[$className];
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw SerializerException::fromUninstantiableClass($className);
        }

        $case = null;

        foreach ($reflection->getAttributes(CaseStyle::class) as $attribute) {
            $case = $attribute->newInstance()->case;
        }

        $properties = [];
        $parameterDefaults = [];
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $parameterDefaults[$parameter->name] = $parameter->isOptional();

                if ($parameter->isPromoted()) {
                    $property = $reflection->getProperty($parameter->name);

                    $properties[$parameter->name] = $this->build(
                        className: $className,
                        reflector: $property,
                        type: $property->getType(),
                        case: $case,
                        hasDefault: $parameter->isOptional(),
                        constructorParameter: true,
                        readable: $property->isPublic(),
                    );

                    continue;
                }

                $properties[$parameter->name] = $this->build(
                    className: $className,
                    reflector: $parameter,
                    type: $parameter->getType(),
                    case: $case,
                    hasDefault: $parameter->isOptional(),
                    constructorParameter: true,
                    readable: false,
                );
            }
        }

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (isset($properties[$property->name])) {
                if (!$properties[$property->name]->readable) {
                    $properties[$property->name] = $this->build(
                        className: $className,
                        reflector: $property,
                        type: $property->getType(),
                        case: $case,
                        hasDefault: $parameterDefaults[$property->name] ?? false,
                        constructorParameter: true,
                        readable: true,
                    );
                }

                continue;
            }

            $properties[$property->name] = $this->build(
                className: $className,
                reflector: $property,
                type: $property->getType(),
                case: $case,
                hasDefault: $property->hasDefaultValue(),
                constructorParameter: false,
                readable: true,
            );
        }

        $xmlRoot = $case === null
            ? $reflection->getShortName()
            : $case->apply($reflection->getShortName());

        foreach ($reflection->getAttributes(XmlRoot::class) as $attribute) {
            $xmlRoot = $attribute->newInstance()->name;
        }

        return $this->cache[$className] = new ClassMetaData(
            reflection: $reflection,
            properties: \array_values($properties),
            xmlRoot: $xmlRoot,
        );
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    private function build(
        string $className,
        \ReflectionProperty|\ReflectionParameter $reflector,
        ?\ReflectionType $type,
        ?KeyCase $case,
        bool $hasDefault,
        bool $constructorParameter,
        bool $readable,
    ): PropertyMetaData {
        $typeName = null;
        $builtin = false;
        $nullable = true;

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            $builtin = $type->isBuiltin();
            $nullable = $type->allowsNull();

            if ($typeName === 'mixed') {
                $typeName = null;
            }
        } elseif ($type !== null) {
            $nullable = $type->allowsNull();
        }

        $key = $case === null
            ? $reflector->name
            : $case->apply($reflector->name);

        foreach ($reflector->getAttributes(Name::class) as $attribute) {
            $key = $attribute->newInstance()->name;
        }

        $listOf = null;
        $mapOf = null;
        $converter = null;

        foreach ($reflector->getAttributes(ListOf::class) as $attribute) {
            $listOf = $attribute->newInstance()->type;
        }

        foreach ($reflector->getAttributes(MapOf::class) as $attribute) {
            $mapOf = $attribute->newInstance()->type;
        }

        foreach ($reflector->getAttributes(Convert::class) as $attribute) {
            $converterClass = $attribute->newInstance()->converter;

            if (!\is_a($converterClass, ValueConverterInterface::class, true)) {
                throw SerializerException::fromInvalidConverter(
                    className: $className,
                    property: $reflector->name,
                    converter: $converterClass,
                );
            }

            $converter = new $converterClass();
        }

        foreach ($reflector->getAttributes(ValueConverterInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $converter = $attribute->newInstance();
        }

        $xmlItem = null;
        $xmlWrapped = true;

        foreach ($reflector->getAttributes(XmlList::class) as $attribute) {
            $xmlList = $attribute->newInstance();
            $xmlItem = $xmlList->item;
            $xmlWrapped = $xmlList->wrapped;
        }

        return new PropertyMetaData(
            name: $reflector->name,
            key: $key,
            typeName: $typeName,
            builtin: $builtin,
            nullable: $nullable,
            hasDefault: $hasDefault,
            constructorParameter: $constructorParameter,
            readable: $readable,
            ignore: \sizeof($reflector->getAttributes(Ignore::class)) > 0,
            listOf: $listOf,
            mapOf: $mapOf,
            converter: $converter,
            xmlAttribute: \sizeof($reflector->getAttributes(XmlAttribute::class)) > 0,
            xmlText: \sizeof($reflector->getAttributes(XmlText::class)) > 0,
            xmlItem: $xmlItem,
            xmlWrapped: $xmlWrapped,
        );
    }
}
