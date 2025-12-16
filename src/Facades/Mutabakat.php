<?php

namespace Visio\mutabakat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Visio\mutabakat\Mutabakat
 */
class Mutabakat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Visio\mutabakat\Mutabakat::class;
    }
}
