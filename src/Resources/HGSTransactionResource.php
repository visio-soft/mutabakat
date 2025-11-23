<?php

namespace Visiosoft\Mutabakat\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Visiosoft\Mutabakat\Models\HGSTransaction;
use Visiosoft\Mutabakat\Resources\HGSTransactionResource\Pages;

class HGSTransactionResource extends Resource
{
    protected static ?string $model = HGSTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'HGS Transactions';

    protected static ?string $modelLabel = 'HGS Transaction';

    protected static ?string $pluralModelLabel = 'HGS Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Park & Session Information')
                    ->schema([
                        Forms\Components\TextInput::make('park_id')
                            ->label('Park ID')
                            ->numeric(),
                        Forms\Components\TextInput::make('matched_session_id')
                            ->label('Matched Session ID')
                            ->numeric(),
                        Forms\Components\TextInput::make('parking_name')
                            ->label('Parking Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('institution_name')
                            ->label('Institution Name')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('plate')
                            ->label('Plate')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('hgs_product_number')
                            ->label('HGS Product Number')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('lane_info')
                            ->label('Lane Info')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('provision_date')
                            ->label('Provision Date'),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date'),
                        Forms\Components\DateTimePicker::make('entry_date')
                            ->label('Entry Date'),
                        Forms\Components\DateTimePicker::make('exit_date')
                            ->label('Exit Date'),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₺')
                            ->default(0),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission Amount')
                            ->numeric()
                            ->prefix('₺')
                            ->default(0),
                        Forms\Components\TextInput::make('net_transfer_amount')
                            ->label('Net Transfer Amount')
                            ->numeric()
                            ->prefix('₺')
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('System Information')
                    ->schema([
                        Forms\Components\TextInput::make('row_hash')
                            ->label('Row Hash')
                            ->maxLength(32)
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('park_id')
                    ->label('Park ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('plate')
                    ->label('Plate')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parking_name')
                    ->label('Parking Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('institution_name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Entry')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exit_date')
                    ->label('Exit')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provision_date')
                    ->label('Provision Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('net_transfer_amount')
                    ->label('Net Transfer')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('hgs_product_number')
                    ->label('HGS Product')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lane_info')
                    ->label('Lane')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('matched_session_id')
                    ->label('Matched Session')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('provision_date')
                    ->form([
                        Forms\Components\DatePicker::make('provision_from')
                            ->label('Provision From'),
                        Forms\Components\DatePicker::make('provision_until')
                            ->label('Provision Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['provision_from'], fn ($q, $date) => $q->whereDate('provision_date', '>=', $date))
                            ->when($data['provision_until'], fn ($q, $date) => $q->whereDate('provision_date', '<=', $date));
                    }),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('entry_from')
                            ->label('Entry From'),
                        Forms\Components\DatePicker::make('entry_until')
                            ->label('Entry Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['entry_from'], fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                            ->when($data['entry_until'], fn ($q, $date) => $q->whereDate('entry_date', '<=', $date));
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('entry_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHGSTransactions::route('/'),
            'create' => Pages\CreateHGSTransaction::route('/create'),
            'view' => Pages\ViewHGSTransaction::route('/{record}'),
            'edit' => Pages\EditHGSTransaction::route('/{record}/edit'),
        ];
    }
}
