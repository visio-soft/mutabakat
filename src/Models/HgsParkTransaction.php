<?php

namespace Visio\mutabakat\Models;

use App\Models\Park;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HgsParkTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'hgs_park_transactions';

    protected $fillable = [
        'park_id',
        'provision_date',
        'payment_date',
        'entry_date',
        'exit_date',
        'plate',
        'hgs_product_number',
        'institution_name',
        'parking_name',
        'lane_info',
        'description',
        'reference_number',
        'amount',
        'commission_amount',
        'net_transfer_amount',
        'row_hash',
    ];

    protected $casts = [
        'provision_date' => 'datetime',
        'payment_date' => 'date',
        'entry_date' => 'datetime',
        'exit_date' => 'datetime',
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_transfer_amount' => 'decimal:2',
    ];

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public static function getByParkAndProvisionDate(int $parkId, $provisionDate): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('park_id', $parkId)
            ->whereDate('provision_date', $provisionDate)
            ->get();
    }

    public static function reportQuery(array $options): Builder
    {
        return self::query()
            ->when($options['parent_parking_name'] ?? null, fn ($q, $name) => $q->where('parking_name', $name))
            ->when($options['provision_date'] ?? null, fn ($q, $date) => $q->whereDate('provision_date', $date));
    }

    public static function exportQuery($parkId, $provisionDate): Builder
    {
        $query = self::query()
            ->where('park_id', $parkId)
            ->whereDate('provision_date', $provisionDate)
            ->orderBy('entry_date', 'asc');

        return $query;
    }

    public static function existsByRowHash(string $rowHash): bool
    {
        return static::query()->where('row_hash', $rowHash)->exists();
    }

    /**
     * Ödeme koleksiyonunda plaka eşleşmesi var mı kontrol eder
     */
    public function hasPlateMatchInPayments($payments): bool
    {
        $cleanPlate = static::cleanPlate($this->plate ?? '');

        if (empty($cleanPlate)) {
            return false;
        }

        return $payments->contains(
            fn ($payment) => static::cleanPlate($payment->plate_txt ?? '') === $cleanPlate
        );
    }

    /**
     * Verilen plakaların içinde olanları filtreler
     */
    public function scopeWithPlatesIn(Builder $query, array $plates): Builder
    {
        if (empty($plates)) {
            return $query->whereRaw('1 = 0'); // Boşsa hiç sonuç döndürme
        }

        $cleanPlates = array_map(fn ($p) => static::cleanPlate($p), $plates);

        return $query->where(function ($q) use ($cleanPlates) {
            foreach ($cleanPlates as $plate) {
                $q->orWhereRaw(
                    'UPPER(REPLACE(REPLACE(REPLACE(plate, \' \', \'\'), \'-\', \'\'), \'.\', \'\')) = ?',
                    [$plate]
                );
            }
        });
    }

    /**
     * Verilen plakaların dışında olanları filtreler
     */
    public function scopeWithPlatesNotIn(Builder $query, array $plates): Builder
    {
        if (empty($plates)) {
            return $query; // Boşsa tüm sonuçları döndür
        }

        $cleanPlates = array_map(fn ($p) => static::cleanPlate($p), $plates);

        return $query->where(function ($q) use ($cleanPlates) {
            foreach ($cleanPlates as $plate) {
                $q->whereRaw(
                    'UPPER(REPLACE(REPLACE(REPLACE(plate, \' \', \'\'), \'-\', \'\'), \'.\', \'\')) != ?',
                    [$plate]
                );
            }
        });
    }

    /**
     * Belirli park ve tarih için query builder döndürür
     */
    public static function queryForParkAndDate(int $parkId, $plate, $entryDate, $exitDate): Builder
    {
        return static::query()
            ->where('park_id', $parkId)
            ->when($plate, fn ($query) => $query->where('plate', $plate))
            ->when($entryDate, fn ($query) => $query->whereDate('entry_date', $entryDate))
            ->when($exitDate, fn ($query) => $query->whereDate('exit_date', $exitDate));
    }

    /**
     * Belirli park ve tarih için toplam tutarı hesaplar
     */
    public static function getTotalAmountForParkAndDate(int $parkId, $date): float
    {
        return static::query()
            ->where('park_id', $parkId)
            ->whereDate('provision_date', $date)
            ->sum('amount') ?? 0;
    }

    /**
     * Belirli park ve tarih için işlem sayısını hesaplar
     */
    public static function getCountForParkAndDate(int $parkId, $date): int
    {
        return static::query()
            ->where('park_id', $parkId)
            ->whereDate('provision_date', $date)
            ->count();
    }

    /**
     * Ödeme koleksiyonundan plaka listesi alır
     */
    public static function getPlatesFromPayments($payments): array
    {
        return $payments
            ->pluck('plate_txt')
            ->filter()
            ->unique()
            ->toArray();
    }

    /**
     * Toplam işlem sayısını hesaplar
     */
    public static function getTotalTransactionCount(): int
    {
        return static::query()->count();
    }

    /**
     * Toplam işlem tutarını hesaplar
     */
    public static function getTotalTransactionAmount(): float
    {
        return static::query()->sum('amount') ?? 0;
    }

    /**
     * Toplam komisyon tutarını hesaplar
     */
    public static function getTotalCommission(): float
    {
        return static::query()->sum('commission_amount') ?? 0;
    }

    /**
     * Toplam net tutar hesaplar
     */
    public static function getTotalNetAmount(): float
    {
        return static::query()->sum('net_transfer_amount') ?? 0;
    }

    /**
     * Plakayı temizler (boşluk, tire ve noktaları kaldırır ve büyük harfe çevirir)
     */
    public static function cleanPlate(?string $plate): string
    {
        if (empty($plate)) {
            return '';
        }

        return strtoupper(str_replace([' ', '-', '.'], '', $plate));
    }
}
