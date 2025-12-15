<?php

namespace Visiosoft\Mutabakat\Exports;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use Visiosoft\Mutabakat\Models\HgsParkTransaction;
use Visiosoft\Mutabakat\Models\Mutabakat;

class HgsParkTransactionExporter implements WithMultipleSheets
{
    protected Collection $data;
    protected array $summary;
    protected array $dailySummary;

    public function __construct(Collection $data, array $summary = [], array $dailySummary = [])
    {
        $this->data = $data;
        $this->summary = $summary;
        $this->dailySummary = $dailySummary;
    }

    public function sheets(): array
    {
        return [
            new HgsParkTransactionDailySheet($this->dailySummary, $this->summary),
            new HgsParkTransactionDetailSheet($this->data, $this->summary),
            new HgsParkTransactionSummarySheet($this->data, $this->summary),

        ];
    }

    public static function downloadFromRecords($records): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $allTransactions = collect();
        $parkIds = collect();
        $dates = collect();

        foreach ($records as $record) {
            if ($record instanceof Mutabakat) {
                $parkIds->push($record->park_id);
                $dates->push($record->provision_date);

                $transactions = HgsParkTransaction::exportQuery($record->park_id, $record->provision_date)
                    ->with(['park', 'matchedSession.payment'])
                    ->get();

                foreach ($transactions as $transaction) {
                    $allTransactions->push(self::mapHgsTransaction($transaction));
                }
            } else {
                $allTransactions->push(self::mapHgsTransaction($record));
            }
        }

        $posTransactions = self::getPosPayments($parkIds->unique()->toArray(), $dates->min(), $dates->max());

        foreach ($posTransactions as $posTransaction) {
            $allTransactions->push(self::mapPosTransaction($posTransaction));
        }

        $startDate = $dates->min() ? Carbon::parse($dates->min())->format('d.m.Y') : null;
        $endDate = $dates->max() ? Carbon::parse($dates->max())->format('d.m.Y') : null;

        $summary = self::calculateSummary($allTransactions);
        $summary['start_date'] = $startDate;
        $summary['end_date'] = $endDate;

        $dailySummary = self::calculateDailySummary($allTransactions);

        $export = new self($allTransactions, $summary, $dailySummary);
        $fileName = 'HGS_POS_Raporu_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download($export, $fileName);
    }

    private static function getPosPayments(array $parkIds, $startDate, $endDate): Collection
    {
        return Mutabakat::getPosPaymentsForExport($parkIds, $startDate, $endDate);
    }

    private static function mapHgsTransaction($record): array
    {
        return [
            'provision_date' => $record->provision_date ? Carbon::parse($record->provision_date)->format('d.m.Y H:i') : null,
            'entry_date' => $record->entry_date ? Carbon::parse($record->entry_date)->format('d.m.Y H:i') : null,
            'exit_date' => $record->exit_date ? Carbon::parse($record->exit_date)->format('d.m.Y H:i') : null,
            'parking_name' => $record->park?->name ?? $record->parking_name ?? '-',
            'plate' => $record->plate,
            'amount' => number_format((float) $record->amount, 2, ',', '.'),
            'amount_raw' => (float) $record->amount,
            'payment_method' => 'HGS',
            'payment_date' => $record->payment_date ? Carbon::parse($record->payment_date)->format('d.m.Y') : null,
        ];
    }

    private static function mapPosTransaction(Payment $record): array
    {
        $session = $record->parkSession;

        return [
            'provision_date' => $record->created_at ? Carbon::parse($record->created_at)->format('d.m.Y H:i') : null,
            'entry_date' => $session?->entry_at ? Carbon::parse($session->entry_at)->format('d.m.Y H:i') : null,
            'exit_date' => $session?->exit_at ? Carbon::parse($session->exit_at)->format('d.m.Y H:i') : null,
            'parking_name' => $record->park?->name ?? '-',
            'plate' => $record->plate_txt ?? $session?->plate_txt ?? '-',
            'amount' => number_format((float) $record->amount, 2, ',', '.'),
            'amount_raw' => (float) $record->amount,
            'payment_method' => $record->service_id?->getLabel() ?? 'POS',
            'payment_date' => $record->created_at ? Carbon::parse($record->created_at)->format('d.m.Y') : null,
        ];
    }

    private static function calculateSummary(Collection $transactions): array
    {
        $hgsTotal = 0;
        $hgsCount = 0;
        $posTotal = 0;
        $posCount = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction['amount_raw'] ?? 0;

            if ($transaction['payment_method'] === 'HGS') {
                $hgsTotal += $amount;
                $hgsCount++;
            } else {
                $posTotal += $amount;
                $posCount++;
            }
        }

        $grandTotal = $hgsTotal + $posTotal;

        return [
            'hgs_total' => $hgsTotal,
            'hgs_count' => $hgsCount,
            'pos_total' => $posTotal,
            'pos_count' => $posCount,
            'grand_total' => $grandTotal,
            'hgs_percent' => $grandTotal > 0 ? round(($hgsTotal / $grandTotal) * 100, 2) : 0,
            'pos_percent' => $grandTotal > 0 ? round(($posTotal / $grandTotal) * 100, 2) : 0,
        ];
    }

    private static function calculateDailySummary(Collection $transactions): array
    {
        $dailyData = [];
        $hgsCommission = 0.0497;

        foreach ($transactions as $transaction) {
            $isHgs = $transaction['payment_method'] === 'HGS';
            
            $date = $transaction['provision_date'] ?? null;
            if (!$date) {
                continue;
            }

            $dateKey = Carbon::parse($date)->format('Y-m-d');
            $displayDate = Carbon::parse($date)->format('d.m.Y');
            $amount = $transaction['amount_raw'] ?? 0;

            if (!isset($dailyData[$dateKey])) {
                $dailyData[$dateKey] = [
                    'date' => $displayDate,
                    'date_sort' => $dateKey,
                    'hgs_count' => 0,
                    'hgs_total' => 0,
                    'hgs_commission' => 0,
                    'hgs_net' => 0,
                    'pos_count' => 0,
                    'pos_total' => 0,
                ];
            }

            if ($isHgs) {
                $dailyData[$dateKey]['hgs_count']++;
                $dailyData[$dateKey]['hgs_total'] += $amount;
                $commission = $amount * $hgsCommission;
                $dailyData[$dateKey]['hgs_commission'] += $commission;
                $dailyData[$dateKey]['hgs_net'] += ($amount - $commission);
            } else {
                $dailyData[$dateKey]['pos_count']++;
                $dailyData[$dateKey]['pos_total'] += $amount;
            }
        }

        usort($dailyData, fn($a, $b) => $a['date_sort'] <=> $b['date_sort']);

        return array_values($dailyData);
    }
}

class HgsParkTransactionDetailSheet implements FromView, WithTitle
{
    protected Collection $data;
    protected array $summary;

    public function __construct(Collection $data, array $summary = [])
    {
        $this->data = $data;
        $this->summary = $summary;
    }

    public function title(): string
    {
        return 'Tüm İşlemler';
    }

    public function view(): View
    {
        return view('reconciliation::exports.reconciliation-comparison', [
            'transactions' => $this->data,
            'sheetType' => 'detail',
            'summary' => $this->summary
        ]);
    }
}

class HgsParkTransactionDailySheet implements FromView, WithTitle
{
    protected array $dailySummary;
    protected array $summary;

    public function __construct(array $dailySummary, array $summary = [])
    {
        $this->dailySummary = $dailySummary;
        $this->summary = $summary;
    }

    public function title(): string
    {
        return 'Tarih Bazlı Detaylar';
    }

    public function view(): View
    {
        return view('reconciliation::exports.reconciliation-comparison', [
            'dailySummary' => $this->dailySummary,
            'sheetType' => 'daily',
            'summary' => $this->summary
        ]);
    }
}

class HgsParkTransactionSummarySheet implements FromView, WithTitle
{
    protected Collection $data;
    protected array $summary;

    public function __construct(Collection $data, array $summary = [])
    {
        $this->data = $data;
        $this->summary = $summary;
    }

    public function title(): string
    {
        return 'Özet';
    }

    public function view(): View
    {
        return view('reconciliation::exports.reconciliation-comparison', [
            'transactions' => $this->data,
            'sheetType' => 'summary',
            'summary' => $this->summary
        ]);
    }
}
