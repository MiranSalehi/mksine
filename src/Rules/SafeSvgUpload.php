<?php

declare(strict_types=1);

namespace Miran\Mksine\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Miran\Mksine\Support\SvgSafety;

/**
 * Rejects uploaded SVG files that can execute script.
 *
 * The mime allowlist alone is not a boundary: `image/svg+xml` is an image type as far as
 * the browser's `Accept` header is concerned, but a document as far as its parser is
 * concerned. Non-SVG uploads pass through untouched.
 */
final class SafeSvgUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (is_array($value) ? $value : [$value] as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mime = $file->getMimeType() ?: $file->getClientMimeType();

            if (! SvgSafety::isSvgUpload($mime, $file->getClientOriginalName())) {
                continue;
            }

            if (! SvgSafety::fileIsSafe((string) $file->getRealPath())) {
                $fail(__('mksine::media.unsafe_svg'));

                return;
            }
        }
    }
}
