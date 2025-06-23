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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\Request\Context\EnvironmentHeaderContext;
use Tuxxedo\Http\Request\Context\EnvironmentInputContext;
use Tuxxedo\Http\Request\Context\EnvironmentServerContext;
use Tuxxedo\Http\Request\Context\EnvironmentUploadedFilesContext;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Mapper\MapperInterface;

readonly class Request implements RequestInterface
{
    public ServerContextInterface $server;
    public HeaderContextInterface $headers;
    public InputContextInterface $cookies;
    public InputContextInterface $get;
    public InputContextInterface $post;
    public UploadedFilesContextInterface $files;

    public function __construct(
        MapperInterface $mapper,
    ) {
        $this->server = new EnvironmentServerContext();
        $this->headers = new EnvironmentHeaderContext();

        $this->cookies = new EnvironmentInputContext(
            superglobal: \INPUT_COOKIE,
            mapper: $mapper,
        );

        $this->get = new EnvironmentInputContext(
            superglobal: \INPUT_GET,
            mapper: $mapper,
        );

        $this->post = new EnvironmentInputContext(
            superglobal: \INPUT_POST,
            mapper: $mapper,
        );

        $this->files = new EnvironmentUploadedFilesContext();
    }
}
