<?php

namespace Miran\Mksine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Miran\Mksine\Mksine
 */
class Mksine extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Miran\Mksine\Mksine::class;
    }
}
