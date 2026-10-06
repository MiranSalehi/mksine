<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

use RuntimeException;
use Throwable;

final class MarketplaceException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $error = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function isSignatureFailure(): bool
    {
        return in_array($this->error, [
            'signature_missing',
            'signature_invalid',
            'signature_unavailable',
        ], true);
    }
}
