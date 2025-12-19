<?php

namespace Visio\mutabakat\Resources\MutabakatComparisonResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Visio\mutabakat\Models\Mutabakat;

class ComparisonStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public array $tableFilters = [];

    protected function getStats(): array
    {
        $parkingName = $this->tableFilters['parent_parking_name']['value'] ?? null;
        $dateFrom = $this->tableFilters['provision_date']['provision_date']['from'] ?? null;
        $dateTo = $this->tableFilters['provision_date']['provision_date']['to'] ?? null;
        
        return [
            Stat::make('Toplam İşlem Sayısı', Mutabakat::getTotalTransactionCount($parkingName, $dateFrom, $dateTo))
                ->label('İşlem Sayısı')
                ->color('success'),
            Stat::make('Toplam Tutar', Number::format(Mutabakat::getTotalAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('Toplam Tutar')
                ->color('warning')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Toplam HGS Komisyon Tutarı', Number::format(Mutabakat::getTotalCommissionAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('HGS Komisyon Tutarı')
                ->color('danger')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Toplam Transfer Tutarı', Number::format(Mutabakat::getTotalNetTransferAmount($parkingName, $dateFrom, $dateTo), 2) . ' ₺')
                ->label('Transfer Tutarı')
                ->color('info')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
