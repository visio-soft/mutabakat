<?php

namespace Visio\mutabakat\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Visio\mutabakat\Enums\FinanceAgreementEnum;
use Visio\mutabakat\Models\Mutabakat;
use Visio\mutabakat\Resources\MutabakatResource\Pages;

class MutabakatResource extends Resource
{
    protected static ?string $model = Mutabakat::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationGroup = 'Mutabakat';

    protected static ?string $pluralModelLabel = "Hgs'den Gelen Raporları";

    protected static ?string $modelLabel = 'Mutabakat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('row_hash')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('provision_date'),
                Forms\Components\TextInput::make('parking_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('parent_parking_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_count')
                    ->numeric(),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('commission_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('net_transfer_amount')
                    ->numeric(),
                Forms\Components\TextInput::make('payment_date')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('provision_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('provision_date')
                    ->label('Provizyon Tarihi')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('park.name')
                    ->label('Park Adı')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_name')
                    ->label('İşlem Adı')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_count')
                    ->label('İşlem Adedi')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Toplam')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Toplam Tutar')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺'.number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('HGS Komisyon Tutarı')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺'.number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('net_transfer_amount')
                    ->label('Ödenecek Tutar')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺'.number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('payment_date')
                    ->dateTime('d-m-Y')
                    ->label('Tetra Ödeme Tarihi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Silinme Tarihi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Oluşturulma Tarihi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Güncellenme Tarihi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_parking_name')
                    ->label('Otopark Adı')
                    ->options(Mutabakat::getParentParkingNameOptions())
                    ->searchable()
                    ->multiple(false)
                    ->preload(false),
                DateRangeFilter::make('provision_date')
                    ->label('Provizyon Tarih Aralığı')
                    ->useColumn('provision_date'),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detay')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(function (Mutabakat $record): string {
                        $provisionDate = $record->provision_date->format('d/m/Y');
                        $dateRange = $provisionDate.' - '.$provisionDate;

                        return HGSParkTransactionResource::getUrl('index', [
                            'tableFilters[park_id][value]' => $record->park_id,
                            'tableFilters[provision_date][provision_date]' => $dateRange,
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label('Durum Değiştir')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Yeni Durum')
                                ->options([
                                    FinanceAgreementEnum::Waiting->value => FinanceAgreementEnum::Waiting->getLabel(),
                                    FinanceAgreementEnum::Done->value => FinanceAgreementEnum::Done->getLabel(),
                                    FinanceAgreementEnum::InProgress->value => FinanceAgreementEnum::InProgress->getLabel(),
                                ])
                                ->required()
                                ->placeholder('Durum seçiniz'),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                                $count++;
                            }

                            $statusLabel = FinanceAgreementEnum::from($data['status'])->getLabel();

                            Notification::make()
                                ->title('Durum Güncellendi')
                                ->body("{$count} kayıt için durum '{$statusLabel}' olarak güncellendi.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Durum Değiştir')
                        ->modalDescription('Seçilen kayıtların durumunu değiştirmek istediğinizden emin misiniz?')
                        ->modalSubmitActionLabel('Evet, Değiştir'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMutabakat::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->notDone();
    }
}
