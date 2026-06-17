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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

#[DefaultImplementation(class: Request::class)]
interface RequestInterface
{
    public HeaderContextInterface $headers {
        get;
    }

    public InputContextInterface $cookies {
        get;
    }

    public InputContextInterface $get {
        get;
    }

    public InputContextInterface $post {
        get;
    }

    public UploadedFilesContextInterface $files {
        get;
    }

    public BodyContextInterface $body {
        get;
    }

    public DispatchableRouteInterface $route {
        get;
    }

    public Method $method {
        get;
    }

    public string $queryString {
        get;
    }

    public string $path {
        get;
    }

    public string $uri {
        get;
    }

    public HttpVersion $protocolVersion {
        get;
    }

    public bool $https {
        get;
    }

    public string $host {
        get;
    }

    public int $port {
        get;
    }

    public string $ipAddress {
        get;
    }

    public function withRoute(
        DispatchableRouteInterface $route,
    ): static;

    public function withMethod(
        Method|string $method,
    ): static;

    public function withPath(
        string $path,
    ): static;

    public function withUri(
        string $uri,
    ): static;

    public function withQueryString(
        string $queryString,
    ): static;

    public function withProtocolVersion(
        HttpVersion $protocolVersion,
    ): static;

    public function withHttps(
        bool $https,
    ): static;

    public function withHost(
        string $host,
    ): static;

    public function withPort(
        int $port,
    ): static;

    public function withIpAddress(
        string $ipAddress,
    ): static;

    public function input(
        InputContext $context,
    ): InputContextInterface;

    public function prefers(
        string ...$supported,
    ): ?string;

    public function accepts(
        string $mimeType,
    ): bool;

    public function acceptsAny(
        string ...$mimeTypes,
    ): bool;

    public function acceptsJson(): bool;

    public function acceptsHtml(): bool;

    public function acceptsCsv(): bool;

    public function acceptsXml(): bool;

    public function acceptsText(): bool;

    public function isModified(
        ?string $etag = null,
        ?\DateTimeInterface $lastModified = null,
    ): bool;

    public function isNotModified(
        ?string $etag = null,
        ?\DateTimeInterface $lastModified = null,
    ): bool;
}
