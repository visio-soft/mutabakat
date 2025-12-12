<?php

namespace Visiosoft\Reconciliation\Services;

use App\Models\Park;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Visiosoft\Reconciliation\Models\HgsParkTransaction;
use Visiosoft\Reconciliation\Models\Reconciliation;

class ReconciliationImportService
{
    private array $allowedHeaders = [
        'Provizyon Tarihi', 'Otopark Adı', 'Bağlı Otopark Adı', 'İşlem Adı', 'İşlem Adedi',
        'Toplam Tutar', 'Komisyon Tutarı', 'Otoparka Aktarılacak Net Tutar', 'Tetra\'dan Otoparka Ödeme Tarihi',
    ];

    private array $allowedParkSessionHeaders = [
        'Provizyon Tarihi', 'Tetra\'dan Otoparka Ödeme Tarihi', 'Giriş Tarihi', 'Çıkış Tarihi',
        'Plaka', 'HGS Ürün Numarası', 'Kurum Adı', 'Otopark Adı', 'Lane Bilgisi',
        'Açıklama', 'Referans Numarası', 'Tutar', 'Komisyon Tutarı', 'Otoparka Aktarılacak Tutar',
    ];

    // Park önbelleği kaldırılmıştır. Gerekirse Park::all() metodu optimize edilebilir.

    public function processXlsxFile($filePath): array
    {
        try {
            $fullPath = $this->resolveFilePath($filePath);

            if (! $fullPath || ! file_exists($fullPath)) {
                return [
                    'status' => 'error',
                    'errors' => ['Dosya bulunamadı. Dosya yolu: '.($filePath ?? 'null')],
                ];
            }

            $zip = new \ZipArchive;
            $result = $zip->open($fullPath);

            if ($result !== true) {
                $error = match ($result) {
                    \ZipArchive::ER_NOZIP => 'Dosya geçerli bir ZIP/XLSX dosyası değil',
                    \ZipArchive::ER_INCONS => 'ZIP dosyası tutarsız',
                    \ZipArchive::ER_CRC => 'CRC hatası',
                    \ZipArchive::ER_OPEN => 'Dosya açılamadı',
                    \ZipArchive::ER_READ => 'Dosya okunamadı',
                    default => 'Bilinmeyen ZIP hatası: '.$result
                };

                return [
                    'status' => 'error',
                    'errors' => [$error],
                ];
            }

            $sharedStrings = $this->parseSharedStrings($zip);
            $reconciliationRows = $this->parseWorksheet($zip, $sharedStrings, 1);
            $parkSessionRows = $this->parseWorksheet($zip, $sharedStrings, 2);
            $zip->close();

            if (empty($reconciliationRows) && empty($parkSessionRows)) {
                return ['status' => 'error', 'errors' => ['XLSX dosyası boş görünüyor']];
            }

            $results = [
                'status' => 'success',
                'reconciliation' => ['imported' => 0, 'duplicates' => 0, 'errors' => []],
                'park_sessions' => ['imported' => 0, 'duplicates' => 0, 'errors' => []],
            ];

            if (! empty($reconciliationRows)) {
                $headers = $this->findHeaders($reconciliationRows);
                if ($headers) {
                    $validationErrors = $this->validateHeaders($headers);
                    if (empty($validationErrors)) {
                        $reconciliationResult = $this->processRows($reconciliationRows, $headers);
                        $results['reconciliation'] = array_merge($results['reconciliation'], $reconciliationResult);
                    } else {
                        $results['reconciliation']['errors'] = $validationErrors;
                    }
                }
            }

            if (! empty($parkSessionRows)) {
                $parkSessionHeaders = $this->findParkSessionHeaders($parkSessionRows);
                if ($parkSessionHeaders) {
                    $validationErrors = $this->validateParkSessionHeaders($parkSessionHeaders);
                    if (empty($validationErrors)) {
                        $parkSessionResult = $this->processParkSessionRows($parkSessionRows, $parkSessionHeaders);
                        $results['park_sessions'] = array_merge($results['park_sessions'], $parkSessionResult);
                    } else {
                        $results['park_sessions']['errors'] = $validationErrors;
                    }
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('XLSX import failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return [
                'status' => 'error',
                'errors' => ['XLSX import başarısız: '.$e->getMessage()],
            ];
        }
    }

    private function resolveFilePath($filePath): ?string
    {
        if (is_string($filePath)) {
            $tmpPath = storage_path('app/livewire-tmp/'.$filePath);
            if (file_exists($tmpPath)) {
                return $tmpPath;
            } elseif (Storage::disk('public')->exists($filePath)) {
                return Storage::disk('public')->path($filePath);
            } elseif (Storage::disk('local')->exists($filePath)) {
                return Storage::disk('local')->path($filePath);
            } elseif (file_exists($filePath)) {
                return $filePath;
            }
        } elseif (is_object($filePath) && method_exists($filePath, 'getRealPath')) {
            return $filePath->getRealPath();
        }

        return null;
    }

    private function dateFormat($date)
    {
        if (! $date || trim($date) === '' || strtolower($date) === 'null') {
            return now()->format('Y-m-d');
        }

        if (is_numeric($date) && $date > 20000 && $date < 90000) {
            $unix = ($date - 25569) * 86400;
            $dt = new \DateTime('@'.($unix + 10800)); // +3 saat ekleme
            $dt->setTimezone(new \DateTimeZone('Europe/Istanbul'));

            return $dt->format('Y-m-d');
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('/^(\d{4})[-/](\d{2})[-/](\d{2})$/', $date)) {
            return date('Y-m-d', strtotime($date));
        }

        $timestamp = strtotime($date.' +3 hours');
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }

        return now()->format('Y-m-d');
    }

    private function parseSharedStrings(\ZipArchive $zip): array
    {
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $sharedStrings[] = isset($si->t) ? (string) $si->t : '';
                }
            }
        }

        return $sharedStrings;
    }

    private function parseWorksheet(\ZipArchive $zip, array $sharedStrings, int $sheetNumber = 1): array
    {
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet'.$sheetNumber.'.xml');
        if (! $worksheetXml) {
            return [];
        }

        $xml = simplexml_load_string($worksheetXml);
        if (! $xml) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $rowElement) {
            $rowData = [];
            foreach ($rowElement->c as $cell) {
                $value = '';
                if (isset($cell->v)) {
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $index = (int) $cell->v;
                        $value = $sharedStrings[$index] ?? '';
                    } else {
                        $value = (string) $cell->v;
                    }
                } elseif (isset($cell->is->t)) {
                    $value = (string) $cell->is->t;
                }
                // Hücre pozisyonunu doğru tutmak için boş hücrelere de boş string ekle
                // Ancak Excel okuyucular boş hücreleri atlayarak sadece dolu hücreleri verir.
                // Basit XML okuma ile sütun indeksini korumak zor olduğu için sadece değerleri alıyoruz.
                $rowData[] = trim($value);
            }
            // Tüm hücreleri boş olan satırları filtrele
            if (! empty(array_filter($rowData, fn ($cell) => ! empty(trim($cell))))) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    private function findHeaderRowIndex(array $rows, array $headers): int
    {
        foreach ($rows as $index => $row) {
            $trimmedRow = array_map('trim', $row);
            if ($trimmedRow === $headers) {
                return $index;
            }
        }

        return 0; // Başlık satırı bulunamazsa ilk satırdan başla varsayımı
    }

    private function findHeaders(array $rows, array $searchKeys = ['Provizyon Tarihi', 'Otopark Adı', 'İşlem Adı']): ?array
    {
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $currentRow = $rows[$i];
            foreach ($searchKeys as $key) {
                if (in_array($key, $currentRow)) {
                    return array_map('trim', $currentRow);
                }
            }
        }

        return ! empty($rows) ? array_map('trim', $rows[0]) : null;
    }

    private function validateHeaders(array $headers): array
    {
        $errors = [];
        $cleanHeaders = array_filter($headers, fn ($header) => ! empty(trim($header)));

        foreach ($cleanHeaders as $header) {
            if (! in_array($header, $this->allowedHeaders)) {
                $errors[] = "Geçersiz başlık: '{$header}'.";
            }
        }

        $requiredHeaders = ['Provizyon Tarihi', 'Otopark Adı', 'İşlem Adı', 'İşlem Adedi', 'Toplam Tutar'];
        foreach ($requiredHeaders as $required) {
            if (! in_array($required, $cleanHeaders)) {
                $errors[] = "Gerekli başlık eksik: '{$required}'";
            }
        }

        return $errors;
    }

    private function processRows(array $rows, array $headers): array
    {
        $results = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => [],
            'total_rows' => 0,
        ];

        $headerIndex = $this->findHeaderRowIndex($rows, $headers);
        $dataRows = array_slice($rows, $headerIndex + 1);

        foreach ($dataRows as $rowIndex => $row) {
            $results['total_rows']++;

            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $data = $this->mapRowToData($row, $headers);

                if (Str::contains(Str::lower($data['transaction_name'] ?? ''), 'toplam')) {
                    continue;
                }

                $parentParkingName = $data['parent_parking_name'] ?: $data['parking_name'];

                if (empty($parentParkingName) || empty($data['provision_date'])) {
                    $results['errors'][] = 'Satır '.($rowIndex + 2).": Otopark adı ve provizyon tarihi zorunlu - Otopark: '{$parentParkingName}', Tarih: '{$data['provision_date']}'";

                    continue;
                }

                $data['provision_date'] = $this->dateFormat($data['provision_date']);
                $data['payment_date'] = $this->dateFormat($data['payment_date'] ?? null);

                $rowHash = md5($data['provision_date'].$parentParkingName.$data['transaction_name']);
                $data['row_hash'] = $rowHash;

                $data['park_id'] = $this->matchParkingNames($parentParkingName);

                if (! $data['park_id']) {
                    $results['errors'][] = 'Satır '.($rowIndex + 2)." - Park bulunamadı: '".$parentParkingName."'";

                    continue;
                }

                if (Reconciliation::existsByRowHash($rowHash)) {
                    $results['duplicates']++;

                    continue;
                }
                Reconciliation::create($data);
                $results['imported']++;

            } catch (\Exception $e) {
                $results['errors'][] = 'Satır '.($rowIndex + 2).' işlenirken hata: '.$e->getMessage().' - Veri: '.json_encode($row);
            }
        }

        return $results;
    }

    private function mapRowToData(array $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            if (! empty($header)) {
                $cell = $row[$i] ?? '';
                $data[$this->mapHeaderToColumn($header)] = trim($cell);
            }
        }

        $data['transaction_count'] = (int) ($data['transaction_count'] ?? 0);
        $data['total_amount'] = $this->formatAmount($data['total_amount'] ?? 0);
        $data['commission_amount'] = $this->formatAmount($data['commission_amount'] ?? 0);
        $data['net_transfer_amount'] = $this->formatAmount($data['net_transfer_amount'] ?? 0);

        return $data;
    }

    private function mapHeaderToColumn(string $header): string
    {
        $mapping = [
            'Provizyon Tarihi' => 'provision_date',
            'Otopark Adı' => 'parking_name',
            'Bağlı Otopark Adı' => 'parent_parking_name',
            'İşlem Adı' => 'transaction_name',
            'İşlem Adedi' => 'transaction_count',
            'Toplam Tutar' => 'total_amount',
            'Komisyon Tutarı' => 'commission_amount',
            'Otoparka Aktarılacak Net Tutar' => 'net_transfer_amount',
            'Tetra\'dan Otoparka Ödeme Tarihi' => 'payment_date',
        ];

        return $mapping[$header] ?? strtolower(str_replace(' ', '_', $header));
    }

    private function findParkSessionHeaders(array $rows): ?array
    {
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $currentRow = $rows[$i];
            if (
                in_array('Provizyon Tarihi', $currentRow) ||
                in_array('Giriş Tarihi', $currentRow) ||
                in_array('Plaka', $currentRow)
            ) {
                return array_map('trim', $currentRow);
            }
        }

        return ! empty($rows) ? array_map('trim', $rows[0]) : null;
    }

    private function validateParkSessionHeaders(array $headers): array
    {
        $errors = [];
        $cleanHeaders = array_filter($headers, fn ($header) => ! empty(trim($header)));

        foreach ($cleanHeaders as $header) {
            if (! in_array($header, $this->allowedParkSessionHeaders)) {
                $errors[] = "Park session için geçersiz başlık: '{$header}'";
            }
        }

        $requiredHeaders = ['Provizyon Tarihi', 'Giriş Tarihi', 'Plaka', 'Otopark Adı', 'Tutar'];
        foreach ($requiredHeaders as $required) {
            if (! in_array($required, $cleanHeaders)) {
                $errors[] = "Park session için gerekli başlık eksik: '{$required}'";
            }
        }

        return $errors;
    }

    private function processParkSessionRows(array $rows, array $headers): array
    {
        $results = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => [],
            'total_rows' => 0,
        ];

        $headerIndex = $this->findHeaderRowIndex($rows, $headers);
        $dataRows = array_slice($rows, $headerIndex + 1);

        foreach ($dataRows as $rowIndex => $row) {
            $results['total_rows']++;

            if (empty(array_filter($row))) {
                continue;
            }

            $data = $this->mapParkSessionRowToData($row, $headers);

            if (empty($data['plate']) || empty($data['provision_date'])) {
                continue;
            }

            try {
                $data['provision_date'] = $this->formatDateTime($data['provision_date']);
                $data['payment_date'] = $this->dateFormat($data['payment_date'] ?? null);
                $data['entry_date'] = $this->formatDateTime($data['entry_date'] ?? null);
                $data['exit_date'] = $this->formatDateTime($data['exit_date'] ?? null);

                $rowHash = md5($data['provision_date'].$data['description'].$data['plate'].$data['parking_name'].($data['entry_date'] ?? ''));
                $data['row_hash'] = $rowHash;

                $data['park_id'] = $this->matchParkingNames($data['parking_name']);

                if (! $data['park_id']) {
                    $results['errors'][] = 'Satır '.($rowIndex + 2)." - Park bulunamadı: '".$data['parking_name']."'";

                    continue;
                }

                if (HgsParkTransaction::existsByRowHash($rowHash)) {
                    $results['duplicates']++;

                    continue;
                }

                HgsParkTransaction::create($data);
                $results['imported']++;

            } catch (\Exception $e) {
                $results['errors'][] = 'Park session satır '.($rowIndex + 2).' işlenirken hata: '.$e->getMessage();
                Log::error('Park session transaction import error', [
                    'row' => $rowIndex + 2,
                    'error' => $e->getMessage(),
                    'data' => $data ?? [],
                ]);
            }
        }

        return $results;
    }

    private function mapParkSessionRowToData(array $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            if (! empty($header)) {
                $cell = $row[$i] ?? '';
                $data[$this->mapParkSessionHeaderToColumn($header)] = trim($cell);
            }
        }

        $data['amount'] = $this->formatAmount($data['amount'] ?? 0);
        $data['commission_amount'] = $this->formatAmount($data['commission_amount'] ?? 0);
        $data['net_transfer_amount'] = $this->formatAmount($data['net_transfer_amount'] ?? 0);

        return $data;
    }

    private function mapParkSessionHeaderToColumn(string $header): string
    {
        $mapping = [
            'Provizyon Tarihi' => 'provision_date',
            'Tetra\'dan Otoparka Ödeme Tarihi' => 'payment_date',
            'Giriş Tarihi' => 'entry_date',
            'Çıkış Tarihi' => 'exit_date',
            'Plaka' => 'plate',
            'HGS Ürün Numarası' => 'hgs_product_number',
            'Kurum Adı' => 'institution_name',
            'Otopark Adı' => 'parking_name',
            'Lane Bilgisi' => 'lane_info',
            'Açıklama' => 'description',
            'Referans Numarası' => 'reference_number',
            'Tutar' => 'amount',
            'Komisyon Tutarı' => 'commission_amount',
            'Otoparka Aktarılacak Tutar' => 'net_transfer_amount',
        ];

        return $mapping[$header] ?? strtolower(str_replace(' ', '_', $header));
    }

    private function formatDateTime($dateTime): ?string
    {
        if (! $dateTime || trim($dateTime) === '' || strtolower($dateTime) === 'null') {
            return null;
        }

        $patterns = [
            '/^(\d{1,2})\.(\d{1,2})\.(\d{4})\s+(\d{1,2}):(\d{2}):(\d{2})$/' => '%04d-%02d-%02d %02d:%02d:%02d',
            '/^(\d{1,2})\.(\d{1,2})\.(\d{4})\s+(\d{1,2}):(\d{2})$/' => '%04d-%02d-%02d %02d:%02d:00',
            '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/' => '%04d-%02d-%02d 00:00:00',
        ];

        foreach ($patterns as $pattern => $format) {
            if (preg_match($pattern, $dateTime, $m)) {
                return $this->formatDateMatches($m, $format);
            }
        }

        if ($this->isExcelSerialDate($dateTime)) {
            return $this->convertExcelSerialDate($dateTime);
        }

        try {
            $dt = new Carbon($dateTime);

            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatDateMatches(array $matches, string $format): string
    {
        return sprintf($format, $matches[3], $matches[2], $matches[1], $matches[4] ?? 0, $matches[5] ?? 0, $matches[6] ?? 0);
    }

    private function isExcelSerialDate($value): bool
    {
        return is_numeric($value) && $value > 20000 && $value < 90000;
    }

    private function convertExcelSerialDate($value): string
    {
        $unix = ($value - 25569) * 86400;
        $dt = new \DateTime('@'.$unix);

        return $dt->format('Y-m-d H:i:s');
    }

    private function formatAmount($value): float
    {
        if (empty($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim($value);

        return (float) $this->normalizeDecimalFormat($value);
    }

    private function normalizeDecimalFormat(string $value): string
    {
        if ($this->hasEuropeanFormat($value)) {
            return $this->convertEuropeanFormat($value);
        }

        if ($this->hasCommaAsThousandsSeparator($value)) {
            return str_replace(',', '', $value);
        }

        if ($this->hasCommaAsDecimalSeparator($value)) {
            return str_replace(',', '.', $value);
        }

        return $value;
    }

    private function hasEuropeanFormat(string $value): bool
    {
        return strpos($value, '.') !== false && strpos($value, ',') !== false;
    }

    private function convertEuropeanFormat(string $value): string
    {
        $value = str_replace('.', '', $value);

        return str_replace(',', '.', $value);
    }

    private function hasCommaAsThousandsSeparator(string $value): bool
    {
        return substr_count($value, ',') === 1
            && strpos($value, '.') === false
            && strlen(substr($value, strpos($value, ',') + 1)) > 2;
    }

    private function hasCommaAsDecimalSeparator(string $value): bool
    {
        return substr_count($value, ',') === 1
            && strpos($value, '.') === false
            && strlen(substr($value, strpos($value, ',') + 1)) <= 2;
    }

    private function normalizeForDirectComparison(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $str = preg_replace("/[^\p{L}\p{N}\s]/u", '', $str);
        $str = preg_replace("/^toger park\s*/i", '', $str);
        $str = str_replace(['cem evi', 'cemevi'], 'cemevi', $str);
        $str = str_replace(['triko center', 'trikocenter'], 'trikocenter', $str);
        $str = preg_replace('/(\d+)\s*(matbaacılar|matbaacilar)/iu', '$1matbaacilar', $str);
        $str = preg_replace('/(matbaacılar|matbaacilar)\s*(\d+)/iu', '$2matbaacilar', $str);
        $str = preg_replace('/vaditepe\s*(avm|gölevleri|golevleri)?/iu', 'vaditepe', $str);

        $removeWords = ['otoparkı', 'otopark', 'sitesi', 'sanayi', 'avm', 'gölevleri', 'golevleri'];
        foreach ($removeWords as $word) {
            $str = preg_replace('/\b'.preg_quote($word, '/').'\b/iu', '', $str);
        }

        return preg_replace('/\s+/', ' ', trim($str));
    }

    private function normalizeForWordAnalysis(string $str): string
    {
        $str = $this->normalizeForDirectComparison($str);

        $stopWords = [
            'otoparkı', 'otopark', 'parkı', 'park', 'açık', 'kapalı', 'isparta',
            'no', 'katlı', 'zemin', 'bodrum', 'araç', 'sitesi', 'sanayi', 'avm',
        ];
        $str = preg_replace("/\b(".implode('|', $stopWords).")\b/u", '', $str);

        $suffixes = [
            'ı', 'i', 'u', 'ü', 'ın', 'in', 'un', 'ün', 'a', 'e', 'da', 'de', 'ta', 'te', 'dan', 'den',
            'tan', 'ten', 'sı', 'si', 'su', 'sü', 'na', 'ne', 'nda', 'nde',
        ];
        $words = explode(' ', $str);
        $stemmedWords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) > 3) {
                $word = preg_replace('/('.implode('|', $suffixes).')$/u', '', $word);
            }
            $stemmedWords[] = $word;
        }
        $str = implode(' ', $stemmedWords);

        return preg_replace('/\s+/', ' ', trim($str));
    }

    private function extractNumbers(string $str): array
    {
        preg_match_all('/\d+/', $str, $matches);

        return $matches[0] ?? [];
    }

    private function normalizeNumberPosition(string $str): string
    {
        $str = preg_replace('/(\d+)\s*\./', '$1', $str);
        
        return preg_replace('/\s+/', ' ', trim($str));
    }

    public function matchParkingNames(string $parent_parking_name): ?int
    {
        $parks = Park::all(['id', 'name']);
        $sourceName = trim($parent_parking_name);
        $debugScores = [];

        $parent_direct_normalized = $this->normalizeForDirectComparison($sourceName);
        $parent_compact = str_replace(' ', '', $parent_direct_normalized);
        $parentNumbers = $this->extractNumbers($sourceName);

        if (! empty($parent_compact)) {
            foreach ($parks as $park) {
                $park_direct_normalized = $this->normalizeForDirectComparison($park->name);
                $park_compact = str_replace(' ', '', $park_direct_normalized);

                if ($park_compact === $parent_compact) {
                    return $park->id;
                }
            }
        }

        $parent_normalized_words = $this->normalizeForWordAnalysis($sourceName);
        if (empty(trim($parent_normalized_words))) {
            return null;
        }
        $parentWords = array_filter(explode(' ', $parent_normalized_words));
        $parentNumbers = $this->extractNumbers($sourceName);

        $bestMatchId = null;
        $highestScore = 0;

        foreach ($parks as $park) {
            $park_normalized_words = $this->normalizeForWordAnalysis($park->name);
            $parkWords = array_filter(explode(' ', $park_normalized_words));
            $parkNumbers = $this->extractNumbers($park->name);

            if (empty($parkWords)) {
                continue;
            }

            $commonWords = array_intersect($parentWords, $parkWords);
            if (count($commonWords) === 0) {
                continue;
            }

            $allWords = array_unique(array_merge($parentWords, $parkWords));
            $wordScore = (count($commonWords) / count($allWords)) * 100;

            similar_text($parent_normalized_words, $park_normalized_words, $stringSimilarityPercent);

            $score = ($wordScore * 0.7) + ($stringSimilarityPercent * 0.3);

            $hasParentNumber = ! empty($parentNumbers);
            $hasParkNumber = ! empty($parkNumbers);
            $commonNumbers = array_intersect($parentNumbers, $parkNumbers);
            $commonNumbersCount = count($commonNumbers);

            if ($hasParentNumber && $hasParkNumber) {
                if ($commonNumbersCount > 0 && $commonNumbersCount === count($parentNumbers)) {
                    $score += 25;
                } elseif ($commonNumbersCount > 0) {
                    $score += 10;
                } else {
                    $score -= 50;
                }
            } elseif ($hasParentNumber xor $hasParkNumber) {
                $score -= 15;
            }

            $debugScores[] = [
                'Park ID' => $park->id,
                'Park Adı (DB)' => $park->name,
                'Skor' => round($score, 2),
                'Parent Numbers' => implode(',', $parentNumbers),
                'Park Numbers' => implode(',', $parkNumbers),
            ];

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatchId = $park->id;
            }
        }

        if (! $bestMatchId || $highestScore < 60) {
            Log::warning('Park Eşleştirme Başarısız: '.$sourceName, [
                'Aranan Ad' => $sourceName,
                'En Yüksek Skor' => round($highestScore, 2),
                'Seçilen ID' => $bestMatchId,
                'Karşılaştırmalar' => $debugScores,
            ]);

            return null;
        }

        return $bestMatchId;
    }
}
