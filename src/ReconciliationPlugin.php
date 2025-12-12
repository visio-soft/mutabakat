<?php

namespace Visiosoft\Reconciliation;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Visiosoft\Reconciliation\Resources\HGSParkTransactionResource;
use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource;
use Visiosoft\Reconciliation\Resources\ReconciliationResource;

class ReconciliationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'reconciliation';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ReconciliationResource::class,
                ReconciliationComparisonResource::class,
                HGSParkTransactionResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
