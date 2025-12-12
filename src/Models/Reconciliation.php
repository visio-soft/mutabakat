<?php

namespace Visiosoft\Reconciliation\Models;

use Visiosoft\Reconciliation\Enums\FinanceAgreementEnum;
use App\Traits\Query\FinancialQueryTrait;
use App\Models\Park;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Visiosoft\Mutabakat\Models\HGSTransaction;

class Reconciliation extends Model
{
    use SoftDeletes,FinancialQueryTrait;

    protected $attributes = [
        'status' => 'waiting',
    ];

    protected $fillable = [
        'row_hash',
        'provision_date',
        'parking_name',
        'parent_parking_name',
        'transaction_name',
        'transaction_count',
        'total_amount',
        'commission_amount',
        'net_transfer_amount',
        'payment_date',
        'status',
        'park_id',
    ];

    protected $casts = [
        'provision_date' => 'datetime',
        'payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_transfer_amount' => 'decimal:2',
        'status' => FinanceAgreementEnum::class,
    ];

    public function scopeSummary(Builder $query): Builder
    {
        return $query
            ->select('parent_parking_name', 'provision_date')
            ->selectRaw('SUM(transaction_count) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->selectRaw('SUM(net_transfer_amount) as net_transfer_amount')
            ->selectRaw('COUNT(*) as reconciliation_count')
            ->selectRaw('MIN(park_id) as park_id')
            ->selectRaw('MIN(id) as id')
            ->groupBy('parent_parking_name', 'provision_date');
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function scopeNotDone(Builder $query): Builder
    {
        return $query->where('status', '!=', FinanceAgreementEnum::Done);
    }

    public static function statusNotDone(): Collection
    {
        return self::notDone()->get();
    }

    public function getParkSessionsCount(): int
    {
        return $this->park?->sessions()
            ->whereDate('exit_at', $this->provision_date)
            ->where('amount', '>', 0)
            ->count() ?? 0;
    }

    public function getSessionAmount(): float
    {
        return $this->park?->sessions()
            ->whereDate('exit_at', $this->provision_date)
             ->where('amount', '>', 0)
            ->sum('amount') ?? 0;
    }

    public function getParkPaymentsCount(): int
    {
        return $this->park?->payments()
            ->whereDate('created_at', $this->provision_date)
             ->where('amount', '>', 0)
            ->where('status_id', 2)
            ->where('service_id', 1)
            ->count() ?? 0;
    }

    public function getPaymentTotal(): float
    {
        return $this->park?->payments()
            ->whereDate('created_at', $this->provision_date)
             ->where('amount', '>', 0)
            ->where('status_id', 2)
            ->where('service_id', 1)
            ->sum('amount') ?? 0;
    }

    public function getHgsTotalAmount(): float
    {
        return HGSTransaction::query()
            ->where('park_id', $this->park_id)
            ->whereDate('exit_date', $this->provision_date)
            ->whereDate('entry_date', $this->provision_date)
            ->whereDate('provision_date', $this->provision_date)
            ->sum('amount') ?? 0;
    }

    public function getHgsTransactionCount(): int
    {
        return HGSTransaction::query()
            ->where('park_id', $this->park_id)
            ->whereDate('exit_date', $this->provision_date)
            ->whereDate('entry_date', $this->provision_date)
            ->whereDate('provision_date', $this->provision_date)
            ->count() ?? 0;
    }


    public function getDifference(): float
    {
        $paymentTotal = $this->getPaymentTotal();
        return $paymentTotal - $this->total_amount;
    }

    public function getZonePaymentTotal(): float
    {
        $parkIds = self::query()
            ->where('parent_parking_name', $this->parent_parking_name)
            ->whereDate('provision_date', $this->provision_date)
            ->whereNotNull('park_id')
            ->pluck('park_id')
            ->unique();

        if ($parkIds->isEmpty()) {
            return 0;
        }

        $hgsServiceIds = [
            \App\Enums\PaymentMethodEnum::HGS->value,
            \App\Enums\PaymentMethodEnum::HGS_BACKEND->value,
        ];

        return \App\Models\Payment::query()
            ->whereIn('park_id', $parkIds)
            ->whereDate('created_at', $this->provision_date)
            ->where('amount', '>', 0)
            ->whereIn('service_id', $hgsServiceIds)
            ->sum('amount') ?? 0;
    }

    public function getZonePaymentCount(): int
    {
        $parkIds = self::query()
            ->where('parent_parking_name', $this->parent_parking_name)
            ->whereDate('provision_date', $this->provision_date)
            ->whereNotNull('park_id')
            ->pluck('park_id')
            ->unique();

        if ($parkIds->isEmpty()) {
            return 0;
        }

        $hgsServiceIds = [
            \App\Enums\PaymentMethodEnum::HGS->value,
            \App\Enums\PaymentMethodEnum::HGS_BACKEND->value,
        ];

        return \App\Models\Payment::query()
            ->whereIn('park_id', $parkIds)
            ->whereDate('created_at', $this->provision_date)
            ->where('amount', '>', 0)
            ->whereIn('service_id', $hgsServiceIds)
            ->count() ?? 0;
    }

    public static function existsByRowHash(string $rowHash): bool
    {
        return static::query()->where('row_hash', $rowHash)->exists();
    }

    public function getParkIdsForComparison(): array
    {
        return static::query()
            ->where('parent_parking_name', $this->parent_parking_name)
            ->whereDate('provision_date', $this->provision_date)
            ->whereNotNull('park_id')
            ->pluck('park_id')
            ->unique()
            ->toArray();
    }

    public static function getPaymentHgsComparison(array $parkIds, $provisionDate): \Illuminate\Support\Collection
    {
        $hgsServiceIds = [
            \App\Enums\PaymentMethodEnum::HGS->value,
            \App\Enums\PaymentMethodEnum::HGS_BACKEND->value,
        ];

        $payments = \App\Models\Payment::query()
            ->whereIn('park_id', $parkIds)
            ->whereDate('created_at', $provisionDate)
            ->where('amount', '>', 0)
            ->whereIn('service_id', $hgsServiceIds)
            ->with(['parkSession'])
            ->get()
            ->map(function ($payment) {
                $cleanPlate = \App\Models\Payment::cleanPlate($payment->plate_txt ?? '');
                return [
                    'id' => $payment->id,
                    'source' => 'zone',
                    'plate' => $payment->plate_txt,
                    'clean_plate' => $cleanPlate,
                    'amount' => $payment->amount,
                    'entry_date' => $payment->parkSession?->entry_at,
                    'exit_date' => $payment->parkSession?->exit_at,
                    'payment_date' => $payment->created_at,
                    'park_id' => $payment->park_id,
                    'payment_id' => $payment->id,
                    'hgs_id' => null,
                    'hgs_amount' => null,
                    'match_status' => 'unmatched',
                ];
            });

        $hgsTransactions = HgsParkTransaction::query()
            ->whereIn('park_id', $parkIds)
            ->whereDate('provision_date', $provisionDate)
            ->get()
            ->map(function ($hgs) {
                $cleanPlate = \App\Models\Payment::cleanPlate($hgs->plate ?? '');
                return [
                    'id' => $hgs->id,
                    'source' => 'hgs',
                    'plate' => $hgs->plate,
                    'clean_plate' => $cleanPlate,
                    'amount' => null,
                    'entry_date' => $hgs->entry_date,
                    'exit_date' => $hgs->exit_date,
                    'payment_date' => $hgs->payment_date,
                    'park_id' => $hgs->park_id,
                    'payment_id' => null,
                    'hgs_id' => $hgs->id,
                    'hgs_amount' => $hgs->amount,
                    'match_status' => 'unmatched',
                ];
            });

        $result = collect();
        $matchedHgsIds = [];

        foreach ($payments as $payment) {
            $matchedHgs = $hgsTransactions->first(function ($hgs) use ($payment, &$matchedHgsIds) {
                if (in_array($hgs['id'], $matchedHgsIds)) {
                    return false;
                }
                if ($payment['clean_plate'] !== $hgs['clean_plate']) {
                    return false;
                }
                if ($payment['entry_date'] && $hgs['entry_date']) {
                    $paymentEntry = $payment['entry_date']->format('Y-m-d H:i');
                    $hgsEntry = $hgs['entry_date']->format('Y-m-d H:i');
                    if ($paymentEntry !== $hgsEntry) {
                        return false;
                    }
                }
                if ($payment['exit_date'] && $hgs['exit_date']) {
                    $paymentExit = $payment['exit_date']->format('Y-m-d H:i');
                    $hgsExit = $hgs['exit_date']->format('Y-m-d H:i');
                    if ($paymentExit !== $hgsExit) {
                        return false;
                    }
                }
                return true;
            });

            if ($matchedHgs) {
                $matchedHgsIds[] = $matchedHgs['id'];
                $payment['hgs_id'] = $matchedHgs['id'];
                $payment['hgs_amount'] = $matchedHgs['hgs_amount'];
                $payment['match_status'] = 'matched';
            }

            $result->push($payment);
        }

        foreach ($hgsTransactions as $hgs) {
            if (!in_array($hgs['id'], $matchedHgsIds)) {
                $result->push($hgs);
            }
        }

        return $result;
    }

    public static function getParentParkingNameOptions(): array
    {
        return self::query()
            ->distinct()
            ->whereNotNull('parent_parking_name')
            ->pluck('parent_parking_name', 'parent_parking_name')
            ->toArray();
    }

    /**
     * Filtrelere göre query builder döndürür
     */
    public static function getFilteredQuery(?string $parkingName = null, ?string $dateFrom = null, ?string $dateTo = null): Builder
    {
        return static::query()
            ->when($parkingName, fn($query) => $query->where('parent_parking_name', $parkingName))
            ->when($dateFrom, fn($query) => $query->whereDate('provision_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('provision_date', '<=', $dateTo));
    }

    /**
     * Filtrelere göre toplam işlem sayısını hesaplar
     */
    public static function getTotalTransactionCount(?string $parkingName = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        return static::getFilteredQuery($parkingName, $dateFrom, $dateTo)->sum('transaction_count') ?? 0;
    }

    /**
     * Filtrelere göre toplam tutarı hesaplar
     */
    public static function getTotalAmount(?string $parkingName = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return static::getFilteredQuery($parkingName, $dateFrom, $dateTo)->sum('total_amount') ?? 0;
    }

    /**
     * Filtrelere göre toplam komisyon tutarını hesaplar
     */
    public static function getTotalCommissionAmount(?string $parkingName = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return static::getFilteredQuery($parkingName, $dateFrom, $dateTo)->sum('commission_amount') ?? 0;
    }

    /**
     * Filtrelere göre toplam net transfer tutarını hesaplar
     */
    public static function getTotalNetTransferAmount(?string $parkingName = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        return static::getFilteredQuery($parkingName, $dateFrom, $dateTo)->sum('net_transfer_amount') ?? 0;
    }

    public static function scopeByPaymentType($query, ?string $paymentType)
    {
        if (empty($paymentType)) {
            return $query;
        }

        $paymentIds = match ($paymentType) {
            'HGS' => [\App\Enums\PaymentMethodEnum::HGS->value, \App\Enums\PaymentMethodEnum::HGS_BACKEND->value],
            default => [],
        };

        if (! empty($paymentIds)) {
            return $query->whereIn('service_id', $paymentIds);
        }

        return $query;
    }

    public static function getPosPaymentsForExport(array $parkIds, $startDate, $endDate): \Illuminate\Support\Collection
    {
        if (empty($parkIds) || ! $startDate || ! $endDate) {
            return collect();
        }

        $posMethodIds = [
            \App\Enums\PaymentMethodEnum::POS->value,
        ];

        return \App\Models\Payment::query()
            ->whereIn('park_id', $parkIds)
            ->whereIn('service_id', $posMethodIds)
            ->where('status_id', \App\Enums\PaymentStatusEnum::PAID)
            ->whereDate('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay())
            ->whereDate('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay())
            ->with(['park', 'parkSession'])
            ->get();
    }

    public static function getPaymentsForReconciliation(array $parkIds, ?string $minDate, ?string $maxDate): \Illuminate\Support\Collection
    {
        return \App\Models\Payment::query()
            ->whereIn('park_id', $parkIds)
            ->when($minDate && $maxDate, fn ($q) => $q->whereBetween('created_at', [$minDate.' 00:00:00', $maxDate.' 23:59:59']))
            ->get(['park_id', 'created_at', 'amount']);
    }

    public static function findMatchingHgsTransaction(\App\Models\ParkSession $session, ?\Illuminate\Support\Collection $hgsTransactions): ?object
    {
        if (! $hgsTransactions || $hgsTransactions->isEmpty()) {
            return null;
        }

        $sessionPlate = \App\Models\Payment::cleanPlate($session->plate_txt ?? '');
        $sessionEntryDate = $session->entry_at;
        $sessionExitDate = $session->exit_at;

        return $hgsTransactions->first(function ($hgs) use ($sessionPlate, $sessionEntryDate, $sessionExitDate) {
            $hgsPlate = \App\Models\Payment::cleanPlate($hgs->plate ?? '');

            if ($sessionPlate !== $hgsPlate) {
                return false;
            }

            if ($sessionEntryDate && $hgs->entry_date) {
                $entryDiff = abs($sessionEntryDate->diffInMinutes($hgs->entry_date));
                if ($entryDiff > 5) {
                    return false;
                }
            }

            if ($sessionExitDate && $hgs->exit_date) {
                $exitDiff = abs($sessionExitDate->diffInMinutes($hgs->exit_date));
                if ($exitDiff > 5) {
                    return false;
                }
            }

            return true;
        });
    }
}
