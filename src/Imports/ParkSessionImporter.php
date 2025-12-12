<?php

namespace Visiosoft\Reconciliation\Imports;

use App\Enums\ParkSessionStatusEnum;
use App\Enums\VehicleClassEnum;
use App\Models\Park;
use App\Models\ParkSession;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

class ParkSessionImporter extends Importer
{
    protected static ?string $model = ParkSession::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('plate_txt')
                ->label('Plaka')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->castStateUsing(fn ($state) => trim(strtoupper(str_replace(' ', '', $state)))),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('park_id')
                ->label('Otopark Seç')
                ->relationship('park', 'name')
                ->preload()
                ->searchable()
                ->required()
                ->placeholder('Bir Otopark seçin'),
        ];
    }

    public function resolveRecord(): ?ParkSession
    {
        do {
            $uuid = Str::uuid()->toString();
        } while (ParkSession::where('session_uid', $uuid)->exists());

        return ParkSession::firstOrNew([
            'park_id' => $this->options['park_id'],
            'entry_at' => now(),
            'plate_txt' => $this->data['plate_txt'],
            'vehicle_class_id' => VehicleClassEnum::CAR->value,
            'photo_entry_id' => null,
            'photo_exit_id' => null,
            'session_status_id' => ParkSessionStatusEnum::NORMAL,
            'session_uid' => $uuid,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Park oturumu içe aktarma tamamlandı. ' . number_format($import->successful_rows) . ' ' . str('satır')->plural($import->successful_rows) . ' içe aktarıldı.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('satır')->plural($failedRowsCount) . ' içe aktarılamadı.';
        }

        return $body;
    }
}
