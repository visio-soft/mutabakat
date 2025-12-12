<?php

namespace Visiosoft\Reconciliation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Visiosoft\Reconciliation\Reconciliation
 */
class Reconciliation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Visiosoft\Reconciliation\Reconciliation::class;
    }
}
