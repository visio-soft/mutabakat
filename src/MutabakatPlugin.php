<?php

namespace Visio\mutabakat;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Visio\mutabakat\Resources\HGSParkTransactionResource;
use Visio\mutabakat\Resources\MutabakatComparisonResource;
use Visio\mutabakat\Resources\MutabakatParkResource;
use Visio\mutabakat\Resources\MutabakatResource;

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
                MutabakatParkResource::class,
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
