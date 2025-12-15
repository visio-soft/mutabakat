<?php

namespace Visiosoft\Mutabakat;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Visiosoft\Mutabakat\Resources\HGSParkTransactionResource;
use Visiosoft\Mutabakat\Resources\MutabakatComparisonResource;
use Visiosoft\Mutabakat\Resources\MutabakatResource;

class MutabakatPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mutabakat';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                MutabakatResource::class,
                MutabakatComparisonResource::class,
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
