<?php

namespace Visio\mutabakat\Resources;

use App\Models\Park;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Visio\mutabakat\Resources\MutabakatParkResource\Pages;

class MutabakatParkResource extends Resource
{
    protected static ?string $model = Park::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    protected static string | \UnitEnum | null $navigationGroup = 'Mutabakat';

    protected static ?string $pluralModelLabel = 'Park Eşleştirmeleri';

    protected static ?string $modelLabel = 'Park Eşleştirme';

    protected static ?string $slug = 'mutabakat-parks';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Mutabakat Eşleştirme')
                    ->description('HGS mutabakat sistemindeki park adını buraya girin. Bu alan, Excel\'den gelen verilerin doğru park ile eşleştirilmesi için kullanılır.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Park Adı')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('mutabakat_park_name')
                            ->label('Mutabakat Park Adı')
                            ->placeholder('HGS sistemindeki park adını girin')
                            ->maxLength(255)
                            ->helperText('Excel dosyasındaki "Otopark Adı" veya "Bağlı Otopark Adı" sütunundaki değer'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Park Adı')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Park adı kopyalandı'),
                Tables\Columns\TextColumn::make('mutabakat_park_name')
                    ->label('Mutabakat Park Adı')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tanımlanmamış')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->copyable()
                    ->copyMessage('Mutabakat park adı kopyalandı'),
                Tables\Columns\IconColumn::make('has_mutabakat_name')
                    ->label('Eşleşme')
                    ->boolean()
                    ->getStateUsing(fn (Park $record) => !empty($record->mutabakat_park_name))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('mutabakat_park_name')
                    ->label('Mutabakat Adı')
                    ->placeholder('Tümü')
                    ->trueLabel('Tanımlı')
                    ->falseLabel('Tanımsız')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('mutabakat_park_name')->where('mutabakat_park_name', '!=', ''),
                        false: fn ($query) => $query->whereNull('mutabakat_park_name')->orWhere('mutabakat_park_name', ''),
                    ),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading(fn (Park $record): string => "{$record->name} - Mutabakat Eşleştirme")
                    ->modalDescription('HGS sistemindeki park adını aşağıdaki alana girin.')
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Park Adı')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('mutabakat_park_name')
                            ->label('Mutabakat Park Adı')
                            ->placeholder('HGS sistemindeki park adını girin')
                            ->maxLength(255)
                            ->helperText('Excel dosyasındaki "Otopark Adı" veya "Bağlı Otopark Adı" sütunundaki değer')
                            ->required(false),
                    ])
                    ->using(function (Park $record, array $data): Park {
                        $record->update([
                            'mutabakat_park_name' => $data['mutabakat_park_name'] ?? null,
                        ]);
                        return $record;
                    })
                    ->successNotificationTitle('Mutabakat park adı güncellendi')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal'),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMutabakatParks::route('/'),
        ];
    }
}
