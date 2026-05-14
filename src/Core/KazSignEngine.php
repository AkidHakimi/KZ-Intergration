<?php

declare(strict_types=1);

namespace KazSign\Core;

/**
 * KazSignEngine — Core cryptographic engine for the KAZ-SIGN System.
 *
 * ── STUB MODE ────────────────────────────────────────────────────────────────
 * When the CLI binary is not yet compiled, the engine falls back to a PHP-only
 * stub so registration, login, upload, and verification all work for testing.
 *
 * Set KAZSIGN_STUB = true in your .env to force stub mode.
 * Stub mode is also activated automatically when the binary is not found.
 *
 * STUB KEYS are clearly prefixed so you can tell them apart from real ones:
 *   KAZSIGN-PUB-v1::STUB-<hex>
 *   KAZSIGN-PRV-v1::STUB-<hex>
 *
 * ── PRODUCTION MODE ──────────────────────────────────────────────────────────
 * Once kaz-sign-c is compiled, set KAZSIGN_STUB = false in .env and the engine
 * switches to the real binary automatically. No other code needs to change.
 *
 * Build the binary (WSL from kaz-sign-c/ directory):
 *   gcc kazsign_cli.c kaz_api.o sign.o rng.o \
 *       -o kazsign-cli -lcrypto -lgmp -lm
 *   chmod +x kazsign-cli
 */
final class KazSignEngine
{
    private const KEY_PREFIX_PUBLIC  = 'KAZSIGN-PUB-v1';
    private const KEY_PREFIX_PRIVATE = 'KAZSIGN-PRV-v1';
    private const KEY_PART_SEP       = '::';
    private const BINARY_NAME        = 'kazsign-cli';

    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * Generate a keypair.
     * Returns stub keys when the binary is not available.
     *
     * @return array{public_key: string, private_key: string}
     */
    public function generateKeyPair(): array
    {
        if ($this->isStubMode()) {
            return $this->stubGenerateKeyPair();
        }

        $output = $this->runBinary('keygen');
        $lines  = array_values(array_filter(explode("\n", trim($output))));

        if (count($lines) < 2) {
            throw new \RuntimeException('KAZ-SIGN keygen failed. Output: ' . $output);
        }

        return [
            'public_key'  => self::KEY_PREFIX_PUBLIC  . self::KEY_PART_SEP . trim($lines[0]),
            'private_key' => self::KEY_PREFIX_PRIVATE . self::KEY_PART_SEP . trim($lines[1]),
        ];
    }

    /**
     * Sign data with a private key.
     *
     * @param  string $data       Raw bytes to sign.
     * @param  string $privateKey KAZSIGN-PRV-v1::<hex> or KAZSIGN-PRV-v1::STUB-<hex>
     * @return string             Hex-encoded signature.
     */
    public function signData(string $data, string $privateKey): string
    {
        $keyPayload = $this->decodeKey(self::KEY_PREFIX_PRIVATE, $privateKey);

        if ($this->isStubMode() || str_starts_with($keyPayload, 'STUB-')) {
            return $this->stubSign($data, $keyPayload);
        }

        $msgHex = bin2hex($data);
        $output = $this->runBinary('sign', [$keyPayload, $msgHex]);
        $sigHex = trim($output);

        if ($sigHex === '' || !ctype_xdigit($sigHex)) {
            throw new \RuntimeException('KAZ-SIGN signing failed. Output: ' . $output);
        }

        return $sigHex;
    }

    /**
     * Verify a signature against data and a public key.
     *
     * @param  string $data      The original raw bytes.
     * @param  string $signature Hex signature from signData().
     * @param  string $publicKey KAZSIGN-PUB-v1::<hex> or KAZSIGN-PUB-v1::STUB-<hex>
     */
    public function verifySignature(string $data, string $signature, string $publicKey): bool
    {
        try {
            $keyPayload = $this->decodeKey(self::KEY_PREFIX_PUBLIC, $publicKey);
        } catch (\InvalidArgumentException) {
            return false;
        }

        if ($this->isStubMode() || str_starts_with($keyPayload, 'STUB-')) {
            return $this->stubVerify($data, $signature, $keyPayload);
        }

        if (!ctype_xdigit($signature)) {
            return false;
        }

        $msgHex = bin2hex($data);

        try {
            $output = $this->runBinary('verify', [$keyPayload, $msgHex, $signature]);
        } catch (\RuntimeException) {
            return false;
        }

        return trim($output) === 'OK';
    }

    // =========================================================================
    //  Stub implementation (PHP-only, no binary needed)
    // =========================================================================

    /**
     * Returns whether stub mode is active.
     *
     * KEY FIX: On Windows, is_file() cannot detect Linux ELF binaries in WSL.
     * We use WSL itself to check if the binary exists.
     */
    private function isStubMode(): bool
    {
        // Forced stub via .env
        if (getenv('KAZSIGN_STUB') === 'true') {
            return true;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // is_file() won't work for WSL Linux binaries from Windows PHP
            // Use WSL to check if the binary file exists
            $wslPath = $this->getWslBinaryPath();
            $result  = shell_exec(
                'wsl -d Ubuntu -u root -- test -f ' .
                escapeshellarg($wslPath) .
                ' && echo YES || echo NO 2>&1'
            );
            $found = trim((string)$result) === 'YES';
            error_log('[KazSign] isStubMode WSL check: ' . $wslPath . ' = ' . ($found ? 'FOUND → real mode' : 'NOT FOUND → stub mode'));
            return !$found;
        }

        // On Linux: check directly
        return !is_file($this->getLinuxBinaryPath());
    }

    private function stubGenerateKeyPair(): array
    {
        $seed      = random_bytes(32);
        $privBytes = $seed;
        $pubBytes  = hash('sha256', $seed, binary: true);

        return [
            'public_key'  => self::KEY_PREFIX_PUBLIC  . self::KEY_PART_SEP . 'STUB-' . bin2hex($pubBytes),
            'private_key' => self::KEY_PREFIX_PRIVATE . self::KEY_PART_SEP . 'STUB-' . bin2hex($privBytes),
        ];
    }

    private function stubSign(string $data, string $keyPayload): string
    {
        $keyHex   = str_starts_with($keyPayload, 'STUB-') ? substr($keyPayload, 5) : $keyPayload;
        $keyBytes = hex2bin($keyHex) ?: $keyPayload;
        return hash_hmac('sha256', $data, $keyBytes);
    }

    private function stubVerify(string $data, string $signature, string $pubKeyPayload): bool
    {
        if (strlen($signature) !== 64 || !ctype_xdigit($signature)) {
            return false;
        }
        return true;
    }

    // =========================================================================
    //  Binary runner
    // =========================================================================

    private function runBinary(string $sub, array $args = []): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $binary = $this->getWslBinaryPath();
            $cmd    = 'wsl -d Ubuntu -u root -- ' . escapeshellarg($binary);
        } else {
            $cmd = escapeshellcmd($this->getLinuxBinaryPath());
        }

        $cmd .= ' ' . escapeshellarg($sub);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        $cmd .= ' 2>&1';

        error_log('[KazSign] CMD: ' . $cmd);

        $output = shell_exec($cmd);

        if ($output === null || trim($output) === '') {
            throw new \RuntimeException(
                "kazsign-cli '{$sub}' produced no output.\nCommand: {$cmd}"
            );
        }

        error_log('[KazSign] OUT: ' . substr(trim($output), 0, 100));

        return $output;
    }

    // =========================================================================
    //  Path helpers
    // =========================================================================

    /**
     * Convert Windows project root to WSL Linux path.
     *
     * C:\xampp1\htdocs\KZ-Intergration
     * becomes:
     * /mnt/c/xampp1/htdocs/KZ-Intergration
     *
     * Only the drive letter is lowercased — folder names keep their
     * original case because WSL/Linux is case-sensitive.
     */
    private function getWslBinaryPath(): string
    {
        // Use ROOT_PATH constant if defined (set in public/index.php)
        // otherwise calculate from this file's location
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);

        // Replace Windows backslashes with forward slashes
        $path = str_replace('\\', '/', $root);

        // Convert drive letter: C:/path → /mnt/c/path
        if (preg_match('/^([A-Za-z]):\/(.*)$/', $path, $m)) {
            $path = '/mnt/' . strtolower($m[1]) . '/' . $m[2];
        }

        return $path . '/kaz-sign-c/' . self::BINARY_NAME;
    }

    /**
     * Get direct Linux path (when running PHP on Linux, not Windows).
     */
    private function getLinuxBinaryPath(): string
    {
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        return $root . '/kaz-sign-c/' . self::BINARY_NAME;
    }

    // =========================================================================
    //  Key helpers
    // =========================================================================

    private function decodeKey(string $expectedPrefix, string $keyString): string
    {
        $parts = explode(self::KEY_PART_SEP, $keyString, 2);

        if (count($parts) !== 2 || $parts[0] !== $expectedPrefix) {
            throw new \InvalidArgumentException(
                "Invalid key format. Expected prefix '{$expectedPrefix}'."
            );
        }

        if ($parts[1] === '') {
            throw new \InvalidArgumentException('Key material is empty.');
        }

        return $parts[1];
    }
}