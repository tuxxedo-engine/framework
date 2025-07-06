<?php

declare(strict_types=1);

use App\Services\Logger\Logger;
use App\Services\Logger\LoggerInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Kernel\ErrorHandlerInterface;
use Tuxxedo\Http\Kernel\Kernel;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Router\DynamicRouter;
use Tuxxedo\Services\ComposerServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = Kernel::createFromDirectory(
    directory: __DIR__ . '/../app',
);

$app->serviceProvider(new ComposerServiceProvider());

// @todo No session module support

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
            ResponseInterface $response,
            \Throwable $exception,
        ): ResponseInterface {
            $html = '';
            $html .= '<h2>Exception</h2>';
            $html .= '<pre>';
            $html .= $exception;
            $html .= '</pre>';
            $html .= '<h2>Logger</h2>';

            $logger = $this->container->resolve(LoggerInterface::class);

            if (\sizeof($logger) > 0) {
                $html .= '<pre>';
                $html .= $logger->formatEntries();
                $html .= '</pre>';
            } else {
                $html .= '<em>No log entries</em>';
            }

            return $response->withBody($html);
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
