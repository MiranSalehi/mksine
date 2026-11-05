<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Environment;

use RuntimeException;

/**
 * Reads and updates KEY=value lines in a .env file, keeping comments and unrelated keys.
 * No Laravel dependency, so the standalone web installer can write .env before boot.
 */
final class EnvironmentFileEditor
{
    public function __construct(private readonly string $path) {}

    /**
     * Use an existing .env, or copy .env.example (or start empty) when there is none.
     */
    public static function openOrCreate(string $path, ?string $examplePath = null): self
    {
        if (! is_file($path)) {
            $contents = $examplePath !== null && is_file($examplePath) ? (string) file_get_contents($examplePath) : '';
            self::writeAtomically($path, $contents);
        }

        return new self($path);
    }

    public static function generateAppKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function get(string $key): ?string
    {
        if (preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $this->contents(), $matches) !== 1) {
            return null;
        }

        return self::parseValue($matches[1]);
    }

    /**
     * @param  array<string, string|int|bool|null>  $values
     */
    public function set(array $values): void
    {
        $contents = $this->contents();

        foreach ($values as $key => $value) {
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                throw new RuntimeException("Invalid environment key [{$key}].");
            }

            $line = $key . '=' . self::formatValue($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace_callback($pattern, static fn (): string => $line, $contents, 1);

                continue;
            }

            $contents = rtrim($contents, "\n") . ($contents === '' ? '' : "\n") . $line . "\n";
        }

        self::writeAtomically($this->path, $contents);
    }

    public static function formatValue(string | int | bool | null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new RuntimeException('Environment values cannot contain line breaks.');
        }

        if (preg_match('/^[A-Za-z0-9_.:\/@+=\-,]*$/', $value) === 1) {
            return $value;
        }

        if (! str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
    }

    private static function parseValue(string $raw): string
    {
        $raw = trim($raw);

        if (strlen($raw) >= 2 && $raw[0] === "'" && str_ends_with($raw, "'")) {
            return substr($raw, 1, -1);
        }

        if (strlen($raw) >= 2 && $raw[0] === '"' && str_ends_with($raw, '"')) {
            return str_replace(['\\"', '\\$', '\\\\'], ['"', '$', '\\'], substr($raw, 1, -1));
        }

        return $raw;
    }

    private function contents(): string
    {
        return is_file($this->path) ? (string) file_get_contents($this->path) : '';
    }

    private static function writeAtomically(string $path, string $contents): void
    {
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(4));

        if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException("Could not write [{$path}].");
        }
    }
}
