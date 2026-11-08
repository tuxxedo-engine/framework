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

namespace Tuxxedo\Http\Kernel\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;
use Tuxxedo\Serializer\SerializerException;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorException;

/**
 * @implements DependencyResolverInterface<object|list<object>>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Payload implements DependencyResolverInterface
{
    /**
     * @param class-string|null $className
     * @param list<string>|null $only
     */
    public function __construct(
        private readonly ?string $className = null,
        private readonly ?string $key = null,
        private readonly ?array $only = null,
        private readonly bool $skipUnknownKeys = true,
        private readonly int $flags = 0,
    ) {
    }

    /**
     * @return object|list<object>
     *
     * @throws HttpException
     * @throws SerializerException
     * @throws ValidationException
     * @throws ValidatorException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): object|array {
        $className = $this->className ?? $parameter->getDefaultType() ?? throw HttpException::fromInternalServerError();
        $list = $parameter->getBuiltinType() === 'array';
        $request = $container->resolve(RequestInterface::class);

        try {
            if ($request->body->isJson()) {
                return $list
                    ? $request->body->jsonMapList(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                        flags: $this->flags,
                    )
                    : $request->body->jsonMap(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                        flags: $this->flags,
                    );
            }

            if ($request->body->isXml()) {
                return $list
                    ? $request->body->xmlMapList(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                    )
                    : $request->body->xmlMap(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                    );
            }

            if ($request->body->isForm()) {
                return $list
                    ? $request->post->mapList(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                    )
                    : $request->post->map(
                        className: $className,
                        key: $this->key,
                        only: $this->only,
                        skipUnknownKeys: $this->skipUnknownKeys,
                    );
            }
        } catch (SerializerInputException $exception) {
            throw HttpException::fromBadRequest(
                exception: $exception,
            );
        }

        throw HttpException::fromUnsupportedMediaType();
    }
}
