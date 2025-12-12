<?php

namespace Visiosoft\Reconciliation\Resources\HGSParkTransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Visiosoft\Reconciliation\Resources\HGSParkTransactionResource;
use Visiosoft\Reconciliation\Resources\HGSParkTransactionResource\Widgets\HgsParkTransactionStatsWidget;

class ListHgsParkTransactions extends ListRecords
{
    protected static string $resource = HGSParkTransactionResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('tableFilters')) {
            $urlFilters = request()->get('tableFilters');
            $tableFilters = [];

            if (! empty($urlFilters['park_id']['value'])) {
                $tableFilters['park_id'] = ['value' => $urlFilters['park_id']['value']];
            }

            if (! empty($urlFilters['provision_date']['provision_date'])) {
                $tableFilters['provision_date'] = ['provision_date' => $urlFilters['provision_date']['provision_date']];
            }

            if (! empty($urlFilters['entry_date']['entry_date'])) {
                $tableFilters['entry_date'] = ['entry_date' => $urlFilters['entry_date']['entry_date']];
            }

            if (! empty($urlFilters['exit_date']['exit_date'])) {
                $tableFilters['exit_date'] = ['exit_date' => $urlFilters['exit_date']['exit_date']];
            }

            if (! empty($tableFilters)) {
                $this->tableFilters = $tableFilters;
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Yenile')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refresh()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HgsParkTransactionStatsWidget::class,
        ];
    }
}
