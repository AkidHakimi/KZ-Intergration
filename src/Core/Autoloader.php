<?php

declare(strict_types=1);

namespace KazSign\Core;

/**
 * PSR-4-style autoloader for the KazSign namespace.
 *
 * Namespace root  : KazSign\
 * Source root     : src/
 *
 * KazSign\Core\Database      → src/Core/Database.php
 * KazSign\Controllers\Auth   → src/Controllers/Auth.php
 * KazSign\Models\User        → src/Models/User.php
 */
final class Autoloader
{
    private string $srcRoot;

    public function __construct(string $srcRoot)
    {
        $this->srcRoot = rtrim($srcRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /** Register this autoloader with SPL. */
    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    /** Resolve a fully-qualified class name to a file path and require it. */
    public function load(string $class): void
    {
        $prefix = 'KazSign\\';

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return; // Not our namespace.
        }

        $relative = substr($class, strlen($prefix));
        $file     = $this->srcRoot . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
}
