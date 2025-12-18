<?php

namespace Visio\mutabakat\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Visio\mutabakat\Models\Mutabakat;
use Visio\mutabakat\Resources\MutabakatComparisonResource\Pages;
use Visio\mutabakat\Resources\MutabakatComparisonResource\Widgets\ComparisonStatsWidget;

class MutabakatComparisonResource extends Resource
{
    protected static ?string $model = Mutabakat::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static string | \UnitEnum | null $navigationGroup = 'Mutabakat';

    protected static ?string $navigationLabel = 'Günlük Mutabakat Rapor';

    protected static ?string $modelLabel = 'Günlük Mutabakat Rapor';

    protected static ?string $pluralModelLabel = 'Günlük Mutabakat Raporlar';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('provision_date')
                    ->label('Rapor Tarihi')
                    ->date()
                    ->searchable(),

                Tables\Columns\TextColumn::make('parent_parking_name')
                    ->label('Ana Park Adı')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Park adı kopyalandı')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transaction_count')
                    ->label('İşlem Sayısı')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('zone_payment_total')
                    ->label('Zone Ödeme Tutarı')
                    ->getStateUsing(fn (Mutabakat $record): float => $record->getZonePaymentTotal())
                    ->money('TRY')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('HGS Tutarı')
                    ->money('TRY')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('difference')
                    ->label('Fark')
                    ->getStateUsing(fn (Mutabakat $record): float => $record->getZonePaymentTotal() - ($record->total_amount ?? 0))
                    ->money('TRY')
                    ->alignEnd()
                    ->color(fn ($state): string => $state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
            ])
            ->filters([
                DateRangeFilter::make('provision_date')
                    ->label('Mutabakat Tarihi Aralığı')
                    ->useColumn('provision_date'),

                Tables\Filters\SelectFilter::make('parent_parking_name')
                    ->label('Ana Park')
                    ->options(fn () => Mutabakat::getParentParkingNameOptions()),
            ])
            ->actions([
                Actions\Action::make('session_comparison')
                    ->label('Oturum Detay')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (Mutabakat $record): string => MutabakatComparisonResource::getUrl('session-comparison', [
                        'record' => $record,
                    ])
                    ),

                Actions\Action::make('payment_comparison')
                    ->label('Ödeme Detay')
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->url(fn (Mutabakat $record): string => MutabakatComparisonResource::getUrl('payment-comparison', [
                        'record' => $record,
                    ])
                    ),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->summary();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMutabakatComparisons::route('/'),
            'session-comparison' => Pages\SessionComparison::route('/{record}/sessions'),
            'payment-comparison' => Pages\PaymentComparison::route('/{record}/payments'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ComparisonStatsWidget::class,
        ];
    }
}
