<?php

declare(strict_types=1);

namespace KazSign\Core;

use RuntimeException;

/**
 * Minimal front-controller router.
 *
 * Routes are registered as:
 *   GET  /path  → ControllerClass::method
 *   POST /path  → ControllerClass::method
 *
 * URL parameters use the :name syntax and are passed to the action as an
 * associative array.
 *
 * Example:
 *   $router->get('/document/:id', [DocumentController::class, 'show']);
 */
final class Router
{
    /** @var array<string, array<string, array{class: string, method: string}>> */
    private array $routes = [];

    // -------------------------------------------------------------------------
    // Registration helpers
    // -------------------------------------------------------------------------

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        [$class, $action] = $handler;
        $this->routes[$method][$path] = ['class' => $class, 'method' => $action];
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    /**
     * Match the current request and invoke the controller action.
     *
     * @throws RuntimeException when no route matches.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = (string) $uri;

        // Strip the subdirectory prefix so the app works both at the domain
        // root (http://example.com/) and in a subdirectory
        // (http://localhost/KZ-Intergration/public/).
        // SCRIPT_NAME = /KZ-Intergration/public/index.php
        // base        = /KZ-Intergration/public
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . trim($uri, '/');

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $params = $this->match($pattern, $uri);

            if ($params !== null) {
                $controller = new $handler['class']();
                $controller->{$handler['method']}($params);
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 — Page Not Found</h1>';
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Returns an array of named URL params when $pattern matches $uri,
     * or null when there is no match.
     *
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $uri): ?array
    {
        // Convert :param segments to named regex groups.
        $regex = preg_replace('#:([a-zA-Z_][a-zA-Z0-9_]*)#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches) !== 1) {
            return null;
        }

        // Keep only string-keyed (named) captures.
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
