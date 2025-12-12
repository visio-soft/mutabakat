<?php

namespace Visiosoft\Reconciliation\Resources\HGSParkTransactionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Visiosoft\Reconciliation\Models\HgsParkTransaction;

class HgsParkTransactionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Oturum Adedi', number_format(HgsParkTransaction::count()))
                ->description('Toplam HGS oturum sayısı')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            
            Stat::make('İşlem Tutarı', number_format(HgsParkTransaction::sum('amount'), 2, ',', '.') . ' ₺')
                ->description('Toplam HGS işlem tutarı')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('HGS Komisyonu', number_format(HgsParkTransaction::sum('commission_amount'), 2, ',', '.') . ' ₺')
                ->description('Toplam HGS komisyon tutarı')
                ->icon('heroicon-o-receipt-percent')
                ->color('warning'),

            Stat::make('Net Tutar', number_format(HgsParkTransaction::sum('net_transfer_amount'), 2, ',', '.') . ' ₺')
                ->description('Toplam net tutar')
                ->icon('heroicon-o-calculator')
                ->color('info'),
        ];
    }
}
