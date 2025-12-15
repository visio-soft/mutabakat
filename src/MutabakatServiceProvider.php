<?php

namespace Visiosoft\Mutabakat;

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
use Visiosoft\Mutabakat\Resources\MutabakatResource\Widgets\MutabakatStats;
use Visiosoft\Mutabakat\Resources\MutabakatComparisonResource\Widgets\ComparisonStatsWidget;
use Visiosoft\Mutabakat\Resources\HGSParkTransactionResource\Widgets\HgsParkTransactionStatsWidget;

class MutabakatServiceProvider extends PackageServiceProvider
{
    public static string $name = 'mutabakat';

    public static string $viewNamespace = 'mutabakat';

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
        Livewire::component('visiosoft.mutabakat.resources.mutabakat-resource.widgets.mutabakat-stats', MutabakatStats::class);
        Livewire::component('visiosoft.mutabakat.resources.mutabakat-comparison-resource.widgets.comparison-stats-widget', ComparisonStatsWidget::class);
        Livewire::component('visiosoft.mutabakat.resources.h-g-s-park-transaction-resource.widgets.hgs-park-transaction-stats-widget', HgsParkTransactionStatsWidget::class);

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
                    $file->getRealPath() => base_path("stubs/mutabakat/{$file->getFilename()}"),
                ], 'mutabakat-stubs');
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'visiosoft/mutabakat';
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
