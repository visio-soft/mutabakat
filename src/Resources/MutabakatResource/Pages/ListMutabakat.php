<?php

namespace Visio\mutabakat\Resources\MutabakatResource\Pages;

use Visio\mutabakat\Resources\MutabakatResource;
use Visio\mutabakat\Resources\MutabakatResource\Widgets\MutabakatStats;
use Visio\mutabakat\Jobs\MutabakatImportJob;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListMutabakat extends ListRecords
{
    protected static string $resource = MutabakatResource::class;



    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('import')
                ->label('Toplu Mutabakat İçe Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('files')
                        ->label('Dosyalar')
                        ->multiple()
                        ->minFiles(1)
                        ->maxFiles(50)
                        ->maxSize(10240)
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->helperText('Bir veya daha fazla XLSX dosyası seçebilirsiniz. Tüm dosyalar arka planda işlenecektir.')
                        ->directory('mutabakat-imports')
                        ->preserveFilenames()
                        ->storeFiles()
                ])
                ->action(function ($data) {
                    $files = $data['files'] ?? [];

                    // Boş kontrolü
                    if (empty($files)) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya yüklenemedi.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Tek dosya veya çoklu dosya - her zaman array olarak işle
                    $files = is_array($files) ? $files : [$files];
                    
                    // Boş string veya null değerleri filtrele
                    $files = array_filter($files, fn($f) => !empty($f));

                    if (empty($files)) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Geçerli dosya bulunamadı.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $userId = Auth::id();
                    $dispatchedCount = 0;

                    foreach ($files as $file) {
                        try {
                            // Job'u dispatch et
                            MutabakatImportJob::dispatch($file, $userId);
                            $dispatchedCount++;
                        } catch (\Exception $e) {
                            Log::error('MutabakatImportJob dispatch hatası', [
                                'file' => $file,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $fileText = $dispatchedCount === 1 ? '1 dosya' : "{$dispatchedCount} dosya";

                    if ($dispatchedCount > 0) {
                        Notification::make()
                            ->title('İçe Aktarım Başlatıldı')
                            ->body("{$fileText} arka planda işlenmek üzere kuyruğa eklendi. İşlem tamamlandığında sonuçlar log'lara kaydedilecektir.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Hata')
                            ->body('Hiçbir dosya kuyruğa eklenemedi.')
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Toplu Mutabakat İçe Aktar'),

        ];
    }


}
