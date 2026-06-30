<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\File;

/**
 * Idempotently patches an application's `app/Models/User.php` so it satisfies the
 * MKSine/Filament Shield requirements (FilamentUser contract + InteractsWithMksine trait).
 *
 * Every fresh Laravel app already ships an `App\Models\User`, so MKSine cannot simply
 * publish its own. Instead we surgically add the trait and contract while preserving the
 * developer's existing model. A timestamped backup is written before any change.
 */
final class UserModelPatcher
{
    public const RESULT_ALREADY = 'already';

    public const RESULT_PATCHED = 'patched';

    public const RESULT_UNSUPPORTED = 'unsupported';

    public const RESULT_MISSING = 'missing';

    private const TRAIT_FQCN = 'Miran\\Mksine\\Concerns\\InteractsWithMksine';

    private const TRAIT_SHORT = 'InteractsWithMksine';

    private const CONTRACT_FQCN = 'Filament\\Models\\Contracts\\FilamentUser';

    private const CONTRACT_SHORT = 'FilamentUser';

    public function __construct(private readonly string $path)
    {
    }

    public static function forApp(): self
    {
        return new self(app_path('Models/User.php'));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function patch(): string
    {
        if (! File::exists($this->path)) {
            return self::RESULT_MISSING;
        }

        $contents = File::get($this->path);

        if (str_contains($contents, self::TRAIT_SHORT) && str_contains($contents, self::CONTRACT_SHORT)) {
            return self::RESULT_ALREADY;
        }

        if (! preg_match('/class\s+User\s+extends\s+\w+/', $contents)) {
            return self::RESULT_UNSUPPORTED;
        }

        $patched = $this->addImports($contents);
        $patched = $this->addContract($patched);
        $patched = $this->addTraitUse($patched);

        if ($patched === $contents) {
            return self::RESULT_UNSUPPORTED;
        }

        if (! $this->isParseable($patched)) {
            return self::RESULT_UNSUPPORTED;
        }

        File::copy($this->path, $this->path.'.mksine-backup-'.date('YmdHis'));
        File::put($this->path, $patched);

        return self::RESULT_PATCHED;
    }

    private function addImports(string $contents): string
    {
        $imports = '';

        if (! str_contains($contents, self::CONTRACT_FQCN)) {
            $imports .= 'use '.self::CONTRACT_FQCN.";\n";
        }

        if (! str_contains($contents, self::TRAIT_FQCN)) {
            $imports .= 'use '.self::TRAIT_FQCN.";\n";
        }

        if ($imports === '') {
            return $contents;
        }

        // Insert after the last top-level `use ...;` statement that precedes the class.
        $classPos = (int) strpos($contents, 'class ');
        $head = substr($contents, 0, $classPos);
        $tail = substr($contents, $classPos);

        if (preg_match_all('/^use [^;]+;[ \t]*\n/m', $head, $matches, PREG_OFFSET_CAPTURE)) {
            $last = end($matches[0]);
            $insertAt = (int) $last[1] + strlen($last[0]);

            return substr($head, 0, $insertAt).rtrim($imports)."\n".substr($head, $insertAt).$tail;
        }

        // No imports found: drop them right after the namespace declaration.
        return preg_replace(
            '/^(namespace [^;]+;\s*)$/m',
            "$1\n\n".rtrim($imports),
            $contents,
            1
        ) ?? $contents;
    }

    private function addContract(string $contents): string
    {
        if (preg_match('/class\s+User\s+extends\s+\w+\s+implements\s+[^{]+\{/', $contents)) {
            if (preg_match('/implements\s+[^{]*'.self::CONTRACT_SHORT.'/', $contents)) {
                return $contents;
            }

            return preg_replace(
                '/(class\s+User\s+extends\s+\w+\s+implements\s+[^{]+?)(\s*\{)/',
                '$1, '.self::CONTRACT_SHORT.'$2',
                $contents,
                1
            ) ?? $contents;
        }

        return preg_replace(
            '/(class\s+User\s+extends\s+\w+)(\s*\{)/',
            '$1 implements '.self::CONTRACT_SHORT.'$2',
            $contents,
            1
        ) ?? $contents;
    }

    private function addTraitUse(string $contents): string
    {
        if (preg_match('/^\s*use\s+'.self::TRAIT_SHORT.'\s*;/m', $contents)) {
            return $contents;
        }

        // Insert as the first statement inside the class body.
        return preg_replace(
            '/(class\s+User\s+extends\s+\w+[^{]*\{)/',
            "$1\n    use ".self::TRAIT_SHORT.";\n",
            $contents,
            1
        ) ?? $contents;
    }

    private function isParseable(string $code): bool
    {
        try {
            token_get_all($code, TOKEN_PARSE);

            return true;
        } catch (\ParseError) {
            return false;
        }
    }
}
