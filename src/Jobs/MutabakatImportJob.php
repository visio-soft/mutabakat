<?php

namespace Visio\mutabakat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Visio\mutabakat\Services\MutabakatImportService;

class MutabakatImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 dakika

    private string $filePath;
    private ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, ?int $userId = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('MutabakatImportJob başlatıldı', [
            'file' => $this->filePath,
            'user_id' => $this->userId,
        ]);

        try {
            $importService = app(MutabakatImportService::class);
            $result = $importService->processXlsxFile($this->filePath);

            if ($result['status'] === 'success') {
                $reconciliationData = $result['reconciliation'] ?? ['imported' => 0, 'duplicates' => 0, 'errors' => []];
                $parkSessionData = $result['park_sessions'] ?? ['imported' => 0, 'duplicates' => 0, 'errors' => []];

                Log::info('MutabakatImportJob tamamlandı', [
                    'file' => $this->filePath,
                    'user_id' => $this->userId,
                    'mutabakat_imported' => $reconciliationData['imported'],
                    'mutabakat_duplicates' => $reconciliationData['duplicates'],
                    'park_sessions_imported' => $parkSessionData['imported'],
                    'park_sessions_duplicates' => $parkSessionData['duplicates'],
                    'errors_count' => count($reconciliationData['errors']) + count($parkSessionData['errors']),
                ]);
            } else {
                Log::error('MutabakatImportJob başarısız', [
                    'file' => $this->filePath,
                    'user_id' => $this->userId,
                    'errors' => $result['errors'],
                ]);
            }

            // İşlem bittikten sonra geçici dosyayı sil
            $this->cleanupFile();

        } catch (\Exception $e) {
            Log::error('MutabakatImportJob hata', [
                'file' => $this->filePath,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MutabakatImportJob tamamen başarısız oldu', [
            'file' => $this->filePath,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Başarısız olsa da dosyayı temizle
        $this->cleanupFile();
    }

    /**
     * Geçici dosyayı temizle
     */
    private function cleanupFile(): void
    {
        try {
            // livewire-tmp klasöründeki dosya
            $tmpPath = storage_path('app/livewire-tmp/' . $this->filePath);
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
                Log::info('Geçici dosya silindi', ['path' => $tmpPath]);
            }

            // public disk'teki dosya
            if (Storage::disk('public')->exists($this->filePath)) {
                Storage::disk('public')->delete($this->filePath);
                Log::info('Public dosya silindi', ['path' => $this->filePath]);
            }

            // mutabakat-imports klasöründeki dosya
            $mutabakatPath = 'mutabakat-imports/' . basename($this->filePath);
            if (Storage::disk('local')->exists($mutabakatPath)) {
                Storage::disk('local')->delete($mutabakatPath);
                Log::info('Mutabakat imports dosya silindi', ['path' => $mutabakatPath]);
            }
        } catch (\Exception $e) {
            Log::warning('Dosya temizleme hatası', [
                'file' => $this->filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
