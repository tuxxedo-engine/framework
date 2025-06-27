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

use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;

interface RequestInterface
{
    public ServerContextInterface $server {
        get;
    }

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
}
