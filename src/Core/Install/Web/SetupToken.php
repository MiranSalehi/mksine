<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Web;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * A short code written to SETUP-TOKEN.txt in the application folder, outside the web
 * root. Entering it in the installer proves the visitor can open the server's files,
 * so nobody else who finds a freshly uploaded site can install it.
 */
class SetupToken
{
    public const FILE_NAME = 'SETUP-TOKEN.txt';

    /** No 0/O, 1/I/L: the code is read from a file manager and typed by hand. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LENGTH = 12;

    /**
     * @param  string|null  $directory  Folder for the token file; the application folder when null.
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly ?string $directory = null,
    ) {}

    /**
     * Create the token file when it does not exist yet.
     *
     * @throws RuntimeException When neither the application folder nor storage is writable.
     */
    public function ensureExists(): void
    {
        if ($this->read() !== null) {
            return;
        }

        $contents = $this->generate() . "\n\n"
            . "MKSine setup token. Enter the code above in the installer (/install).\n"
            . "کد بالا را در نصب‌کنندهٔ MKSine (‎/install) وارد کنید.\n"
            . "This file is deleted when installation finishes.\n";

        foreach ($this->candidatePaths() as $path) {
            if (@file_put_contents($path, $contents) !== false) {
                return;
            }
        }

        throw new RuntimeException('Could not write ' . self::FILE_NAME . '.');
    }

    public function verify(string $input): bool
    {
        $expected = $this->read();
        $given = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $input));

        return $expected !== null && $given !== '' && hash_equals($expected, $given);
    }

    /**
     * Where the user finds the file, relative to the folder that contains the application.
     */
    public function displayPath(): string
    {
        $path = $this->existingPath() ?? $this->candidatePaths()[0];
        $root = dirname($this->directory ?? base_path());

        return str_starts_with($path, $root . DIRECTORY_SEPARATOR) ? substr($path, strlen($root) + 1) : $path;
    }

    public function delete(): void
    {
        foreach ($this->candidatePaths() as $path) {
            $this->files->delete($path);
        }
    }

    private function read(): ?string
    {
        $path = $this->existingPath();

        if ($path === null) {
            return null;
        }

        $firstLine = strtok((string) $this->files->get($path), "\n");
        $token = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $firstLine));

        return $token !== '' ? $token : null;
    }

    private function existingPath(): ?string
    {
        foreach ($this->candidatePaths() as $path) {
            if ($this->files->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(): array
    {
        return [
            ($this->directory ?? base_path()) . DIRECTORY_SEPARATOR . self::FILE_NAME,
            storage_path(self::FILE_NAME),
        ];
    }

    private function generate(): string
    {
        $characters = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $characters .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return implode('-', str_split($characters, 4));
    }
}
