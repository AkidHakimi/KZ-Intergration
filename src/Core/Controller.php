<?php

declare(strict_types=1);

namespace KazSign\Core;

/**
 * Base controller.
 *
 * Provides view rendering and simple redirect helpers for every concrete
 * controller in the application.
 */
abstract class Controller
{
    /**
     * Render a view file from src/Views/.
     *
     * Variables in $data are extracted into the view's local scope.
     *
     * @param string               $view  Dot-notation path, e.g. 'auth.login'
     *                                    resolves to src/Views/auth/login.php
     * @param array<string, mixed> $data  Variables to expose inside the view
     */
    protected function render(string $view, array $data = []): void
    {
        $file = SRC_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view} ({$file})");
        }

        // Inject $base into every view so href/action attributes stay correct
        // whether the app runs at domain root or inside a subdirectory.
        $data['base'] = defined('BASE_URL') ? BASE_URL : '';

        extract($data, EXTR_SKIP);
        require $file;
    }

    /**
     * Send an HTTP redirect and stop execution.
     *
     * Relative URLs (starting with /) are automatically prefixed with
     * BASE_URL so the app works in both root and subdirectory deployments.
     */
    protected function redirect(string $url): never
    {
        if (str_starts_with($url, '/') && defined('BASE_URL') && BASE_URL !== '') {
            $url = BASE_URL . $url;
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Return the authenticated user's ID from the session,
     * or null when no session is active.
     */
    protected function authUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Require authentication — redirect to /login when no session exists.
     */
    protected function requireAuth(): void
    {
        if ($this->authUserId() === null) {
            $this->redirect('/login');
        }
    }
}
