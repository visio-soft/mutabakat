<?php

namespace Visiosoft\Mutabakat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Visiosoft\Mutabakat\Mutabakat
 */
class Mutabakat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Visiosoft\Mutabakat\Mutabakat::class;
    }
}
