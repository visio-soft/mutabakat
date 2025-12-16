<?php

namespace Visio\mutabakat\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Visio\mutabakat\Models\HgsParkTransaction;
use Visio\mutabakat\Resources\HGSParkTransactionResource\Pages;

class HGSParkTransactionResource extends Resource
{
    protected static ?string $model = HgsParkTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Mutabakat';

    protected static ?string $pluralModelLabel = 'HGS Geçişleri';

    protected static ?string $modelLabel = 'HGS Geçişi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('plate')
                    ->label('Plaka')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('provision_date')
                    ->label('Provizyon Tarihi'),
                Forms\Components\DateTimePicker::make('entry_date')
                    ->label('Giriş Tarihi'),
                Forms\Components\DateTimePicker::make('exit_date')
                    ->label('Çıkış Tarihi'),
                Forms\Components\TextInput::make('amount')
                    ->label('Tutar')
                    ->numeric()
                    ->prefix('₺'),
                Forms\Components\TextInput::make('commission_amount')
                    ->label('Komisyon')
                    ->numeric()
                    ->prefix('₺'),
                Forms\Components\TextInput::make('net_transfer_amount')
                    ->label('Net Tutar')
                    ->numeric()
                    ->prefix('₺'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provision_date')
                    ->label('Provizyon Tarihi')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('park.name')
                    ->label('Park Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plate')
                    ->label('Plaka')
                    ->searchable()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Count::make()->label('Kayıt')),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Giriş Tarihi')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exit_date')
                    ->label('Çıkış Tarihi')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('İşlem Tutarı')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺' . number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('HGS Komisyonu')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺' . number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('net_transfer_amount')
                    ->label('Net Tutar')
                    ->formatStateUsing(
                        fn (?float $state): ?string => is_numeric($state)
                            ? '₺' . number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.')
                            : null
                    )
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Oluşturulma Tarihi')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('park_id')
                    ->label('Otopark')
                    ->relationship('park', 'name')
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('provision_date')
                    ->label('Provizyon Tarih Aralığı')
                    ->useColumn('provision_date'),
                DateRangeFilter::make('entry_date')
                    ->label('Giriş Tarih Aralığı')
                    ->useColumn('entry_date'),
                DateRangeFilter::make('exit_date')
                    ->label('Çıkış Tarih Aralığı')
                    ->useColumn('exit_date'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('provision_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHgsParkTransactions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
