<?php

namespace Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Visiosoft\Reconciliation\Models\Reconciliation;

class ComparisonStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public array $tableFilters = [];

    protected function getStats(): array
    {
        $parkingName = $this->tableFilters['parent_parking_name']['value'] ?? null;
        $dateFrom = $this->tableFilters['provision_date']['provision_date']['from'] ?? null;
        $dateTo = $this->tableFilters['provision_date']['provision_date']['to'] ?? null;
        
        return [
            Stat::make('Toplam İşlem Sayısı', Reconciliation::getTotalTransactionCount($parkingName, $dateFrom, $dateTo))
                ->label('İşlem Sayısı')
                ->color('success'),
            Stat::make('Toplam Tutar', Number::format(Reconciliation::getTotalAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('Toplam Tutar')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Toplam HGS Komisyon Tutarı', Number::format(Reconciliation::getTotalCommissionAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('HGS Komisyon Tutarı')
                ->color('danger')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Toplam Transfer Tutarı', Number::format(Reconciliation::getTotalNetTransferAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('Transfer Tutarı')
                ->color('info')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
