<?php

namespace Visiosoft\Mutabakat\Resources\MutabakatResource\Pages;

use Visiosoft\Mutabakat\Resources\MutabakatResource;
use Visiosoft\Mutabakat\Resources\MutabakatResource\Widgets\MutabakatStats;
use Visiosoft\Mutabakat\Services\MutabakatImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListMutabakat extends ListRecords
{
    protected static string $resource = MutabakatResource::class;



    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('import')
                ->label('Mutabakat İçe Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file')
                        ->maxSize(10240)
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->helperText('XLSX formatında mutabakat dosyasını yükleyin.')
                ])
                ->action(function ($data) {
                    $file = $data['file'];

                    if (!$file) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya yüklenemedi.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $importService = app(MutabakatImportService::class);

                        // Filament FileUpload'dan gelen dosya yolunu service'e gönder
                        // $file burada livewire-tmp klasöründeki dosya yolu olacak
                        $result = $importService->processXlsxFile($file);

                        if ($result['status'] === 'success') {
                            $reconciliationData = $result['reconciliation'] ?? ['imported' => 0, 'duplicates' => 0, 'errors' => []];
                            $parkSessionData = $result['park_sessions'] ?? ['imported' => 0, 'duplicates' => 0, 'errors' => []];
                            
                            $totalImported = $reconciliationData['imported'] + $parkSessionData['imported'];
                            $totalDuplicates = $reconciliationData['duplicates'] + $parkSessionData['duplicates'];
                            $totalErrors = array_merge($reconciliationData['errors'], $parkSessionData['errors']);

                            $message = "İçe Aktarım Tamamlandı:\n";
                            $message .= "• Mutabakat: {$reconciliationData['imported']} kayıt\n";
                            $message .= "• Park Oturumları: {$parkSessionData['imported']} kayıt\n";
                            $message .= "• Toplam: {$totalImported} kayıt başarıyla aktarıldı";

                            if ($totalDuplicates > 0) {
                                $message .= "\n• {$totalDuplicates} duplicate kayıt atlandı";
                            }

                            if (!empty($totalErrors)) {
                                $message .= "\n• " . count($totalErrors) . " satırda hata oluştu";
                            }

                            Notification::make()
                                ->title('İçe Aktarım Başarılı')
                                ->body($message)
                                ->success()
                                ->send();

                            // Sayfayı yenile
                            $this->redirect(request()->header('Referer'));
                        } else {
                            Notification::make()
                                ->title('İçe Aktarım Hatası')
                                ->body(implode(', ', $result['errors']))
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya işlenirken bir hata oluştu: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Mutabakat İçe Aktar'),

        ];
    }


}
