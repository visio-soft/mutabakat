<?php

namespace Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Pages;

use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource;
use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Widgets\ComparisonStatsWidget;
use Visiosoft\Reconciliation\Exports\HgsParkTransactionExporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListReconciliationComparisons extends ListRecords
{
    protected static string $resource = ReconciliationComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Excel\'e Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $filters = $this->tableFilters;
                        $parkFilter = $filters['parent_parking_name']['value'] ?? null;

                        if (empty($parkFilter)) {
                            Notification::make()
                                ->title('Uyarı')
                                ->body('Lütfen önce bir park seçiniz.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $records = $this->getFilteredTableQuery()->get();
                        
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Uyarı')
                                ->body('Export edilecek veri bulunamadı.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        return HgsParkTransactionExporter::downloadFromRecords($records);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export Hatası')
                            ->body('Excel export sırasında bir hata oluştu: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ComparisonStatsWidget::class,
        ];
    }
}
