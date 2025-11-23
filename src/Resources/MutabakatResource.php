<?php

namespace Visiosoft\Mutabakat\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Visiosoft\Mutabakat\Models\Mutabakat;
use Visiosoft\Mutabakat\Resources\MutabakatResource\Pages;

class MutabakatResource extends Resource
{
    protected static ?string $model = Mutabakat::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Mutabakat';

    protected static ?string $modelLabel = 'Mutabakat';

    protected static ?string $pluralModelLabel = 'Mutabakat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Genel Bilgiler')
                    ->schema([
                        Forms\Components\TextInput::make('park_id')
                            ->label('Park ID')
                            ->numeric(),
                        Forms\Components\TextInput::make('row_hash')
                            ->label('Row Hash')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('provision_date')
                            ->label('Provision Date'),
                        Forms\Components\TextInput::make('company')
                            ->label('Company')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('parking_name')
                            ->label('Parking Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('transaction_name')
                            ->label('Transaction Name')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Finansal Bilgiler')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_count')
                            ->label('Transaction Count')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
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
                    ])->columns(2),

                Forms\Components\Section::make('Durum ve Tarihler')
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ]),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parking_name')
                    ->label('Parking Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_name')
                    ->label('Transaction Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_count')
                    ->label('Count')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('net_transfer_amount')
                    ->label('Net Transfer')
                    ->money('TRY')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('provision_date')
                    ->label('Provision Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMutabakat::route('/'),
            'create' => Pages\CreateMutabakat::route('/create'),
            'edit' => Pages\EditMutabakat::route('/{record}/edit'),
        ];
    }
}
