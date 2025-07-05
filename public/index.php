<?php

declare(strict_types=1);

use App\Services\Logger\Logger;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Services\ComposerServiceProvider;
use Tuxxedo\Services\EngineDefaultsServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Kernel::createFromDirectory(
    directory: __DIR__ . '/../app',
);

$app->serviceProvider(new EngineDefaultsServiceProvider());
$app->serviceProvider(new ComposerServiceProvider());

// @todo Register error handling, depending on what the turn out from the $this->appName
//       verdict above, this may need similar treatment. $this->appProfile will be the main thing
//       that affects the error handling. This needs to likely include a set_error_handler() call.

// @todo Turn this into a debug error handler and register it in the Kernel if DEBUG profile
$app->defaultExceptionHandler(
    new class ($app->container) implements ErrorHandlerInterface {
        public function __construct(
            private readonly Container $container,
        ) {
        }

        public function handle(
            RequestInterface $request,
            \Throwable $exception,
        ): void {
            echo '<h2>Exception</h2>';
            echo '<pre>';
            echo $exception;
            echo '</pre>';

            echo '<h2>Logger</h2>';
            echo '<pre>';
            echo $this->container->resolve(LoggerInterface::class)->formatEntries();
            echo '</pre>';
        }
    },
);

// @todo Implement loading of app/services.php into $this->container, providers?
$app->container->persistent(Logger::class);

// @todo Once the router is registered, look into the routes and where it retrieve its
//       internal database, which could for example be static, app/routes.php,
//       static attributes (via precompiled file) or dynamic attributes via reflection
$app->container->persistent(
    new DynamicRouter(
        container: $app->container,
        directory: __DIR__ . '/../app/Controllers',
        baseNamespace: '\App\Controllers\\',
    ),
);

$app->run();
