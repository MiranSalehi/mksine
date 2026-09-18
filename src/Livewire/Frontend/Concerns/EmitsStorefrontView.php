<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire\Frontend\Concerns;

use Miran\Mksine\Http\StorefrontViewedResponder;

trait EmitsStorefrontView
{
    /**
     * @param  int|string|null  $id
     */
    protected function emitStorefrontView(string $type, mixed $id = null, ?string $status = null): void
    {
        StorefrontViewedResponder::emit([
            'type' => $type,
            'id' => $id,
            'status' => $status,
        ]);
    }
}
