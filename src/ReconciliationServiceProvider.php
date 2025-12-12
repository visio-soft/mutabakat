<?php

namespace Visiosoft\Reconciliation;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Livewire;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Visiosoft\Reconciliation\Resources\ReconciliationResource\Widgets\ReconciliationStats;
use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Widgets\ComparisonStatsWidget;
use Visiosoft\Reconciliation\Resources\HGSParkTransactionResource\Widgets\HgsParkTransactionStatsWidget;

class ReconciliationServiceProvider extends PackageServiceProvider
{
    public static string $name = 'reconciliation';

    public static string $viewNamespace = 'reconciliation';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace);
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        Livewire::component('visiosoft.reconciliation.resources.reconciliation-resource.widgets.reconciliation-stats', ReconciliationStats::class);
        Livewire::component('visiosoft.reconciliation.resources.reconciliation-comparison-resource.widgets.comparison-stats-widget', ComparisonStatsWidget::class);
        Livewire::component('visiosoft.reconciliation.resources.h-g-s-park-transaction-resource.widgets.hgs-park-transaction-stats-widget', HgsParkTransactionStatsWidget::class);

        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        FilamentIcon::register($this->getIcons());

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/reconciliation/{$file->getFilename()}"),
                ], 'reconciliation-stubs');
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'visiosoft/reconciliation';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('mutabakat', __DIR__ . '/../resources/dist/components/mutabakat.js'),
            // Css::make('mutabakat-styles', __DIR__ . '/../resources/dist/mutabakat.css'),
            // Js::make('mutabakat-scripts', __DIR__ . '/../resources/dist/mutabakat.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }
}
