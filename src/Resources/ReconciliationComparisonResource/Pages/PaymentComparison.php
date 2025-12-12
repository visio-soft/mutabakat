<?php

namespace Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Pages;

use App\Enums\PaymentMethodEnum;
use App\Models\Payment;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Filament\Admin\Resources\PaymentResource;
use Visiosoft\Reconciliation\Models\HgsParkTransaction;
use Visiosoft\Reconciliation\Models\Reconciliation;
use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource;

class PaymentComparison extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable, InteractsWithInfolists;

    protected static string $resource = ReconciliationComparisonResource::class;

    protected static string $view = 'reconciliation::pages.payment-comparison';

    public Reconciliation $record;

    public Collection $hgsTransactions;

    public function mount(Reconciliation $record): void
    {
        $this->record = $record;

        $this->hgsTransactions = HgsParkTransaction::getByParkAndProvisionDate(
            $this->record->park_id,
            $this->record->provision_date
        );
    }

    public function getTitle(): string
    {
        return "Ödeme Karşılaştırma - {$this->record->parent_parking_name} ({$this->record->provision_date->format('d.m.Y')})";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::query()
                ->forParkAndDate($this->record->park_id, $this->record->provision_date)
                ->with(['parkSession'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('plate_txt')
                    ->label('Plaka')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => !empty($state) ? $state : '-')
                    ->summarize(Tables\Columns\Summarizers\Count::make()->label('Kayıt')),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Ödeme Türü')
                    ->getStateUsing(function (Payment $record) {
                        if ($record->service_id instanceof PaymentMethodEnum) {
                            return $record->service_id->getPaymentType();
                        }
                        if (is_numeric($record->service_id)) {
                            $paymentMethod = PaymentMethodEnum::tryFrom((int) $record->service_id);
                            return $paymentMethod?->getPaymentType() ?? '-';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'HGS' => 'success',
                        'POS' => 'info',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Giriş Tarihi')
                    ->getStateUsing(fn (Payment $record) => $record->parkSession?->entry_at)
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d-m-Y H:i') : '-'),
                Tables\Columns\TextColumn::make('exit_date')
                    ->label('Çıkış Tarihi')
                    ->getStateUsing(fn (Payment $record) => $record->parkSession?->exit_at)
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d-m-Y H:i') : '-'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Ödeme Tutarı')
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format($state, ($state == (int) $state) ? 0 : 2, ',', '.') . ' ₺' : '-')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ödeme Tarihi')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d-m-Y H:i') : '-'),
                $this->getMatchStatusColumn(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Ödeme Türü')
                    ->options([
                        'HGS' => 'HGS',
                        'POS' => 'POS',
                        'BANKA' => 'Banka Transferi',
                        'BEYAZ LİSTE' => 'Beyaz Liste',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $paymentType = $data['value'] ?? null;
                        if (empty($paymentType)) {
                            return $query;
                        }
                        $paymentIds = match ($paymentType) {
                            'HGS' => [PaymentMethodEnum::HGS->value, PaymentMethodEnum::HGS_BACKEND->value],
                            default => [],
                        };
                        if (!empty($paymentIds)) {
                            return $query->whereIn('service_id', $paymentIds);
                        }
                        return $query;
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function getMatchStatusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('match_status')
            ->label('Sonuç')
            ->getStateUsing(function (Payment $record) {
                if ($this->isNonHgsPayment($record)) {
                    return 'Eşleşti';
                }
                $hasHgsMatch = $this->findMatchingHgsTransaction($record) !== null;
                return $hasHgsMatch ? 'Eşleşti' : 'Ödeme Detayına Git';
            })
            ->color(fn (string $state) => $state === 'Eşleşti' ? 'success' : 'info')
            ->icon(fn (string $state) => $state === 'Eşleşti' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-top-right-on-square')
            ->url(function (Payment $record, string $state): ?string {
                if ($state === 'Eşleşti') {
                    return null;
                }
                $session = $record->parkSession;
                if (!$session) {
                    return null;
                }
                
                $provisionDate = $this->record->provision_date->format('d/m/Y');
                $dateRange = $provisionDate . ' - ' . $provisionDate;
                
                return PaymentResource::getUrl('index') . '?' . http_build_query([
                    'tableFilters' => [
                        'park_id' => [
                            'values' => [$this->record->park_id],
                        ],
                        'created_at' => [
                            'created_at' => $dateRange,
                        ],
                    ],
                    'tableSearch' => $record->plate_txt,
                ]);
            })
            ->openUrlInNewTab();
    }

    private function isNonHgsPayment(Payment $payment): bool
    {
        if ($payment->service_id instanceof PaymentMethodEnum) {
            return !in_array($payment->service_id, [PaymentMethodEnum::HGS, PaymentMethodEnum::HGS_BACKEND]);
        }
        
        if (is_numeric($payment->service_id)) {
            $paymentMethod = PaymentMethodEnum::tryFrom((int) $payment->service_id);
            return $paymentMethod && !in_array($paymentMethod, [PaymentMethodEnum::HGS, PaymentMethodEnum::HGS_BACKEND]);
        }
        
        return false;
    }

    private function findMatchingHgsTransaction(Payment $payment): ?HgsParkTransaction
    {
        $session = $payment->parkSession;
        if (!$session || !$payment->plate_txt) {
            return null;
        }

        $cleanPlate = Payment::cleanPlate($payment->plate_txt);
        
        return $this->hgsTransactions->first(function ($hgsTransaction) use ($cleanPlate, $session) {
            if (Payment::cleanPlate($hgsTransaction->plate ?? '') !== $cleanPlate) {
                return false;
            }

            if ($session->entry_at && $hgsTransaction->entry_date) {
                if (abs($session->entry_at->diffInMinutes($hgsTransaction->entry_date)) > 5) {
                    return false;
                }
            }
            
            if ($session->exit_at && $hgsTransaction->exit_date) {
                if (abs($session->exit_at->diffInMinutes($hgsTransaction->exit_date)) > 5) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    public function summaryInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('Ödeme Özet Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([]),
                    ]),
            ]);
    }
}
