<?php

namespace Visio\mutabakat\Resources\MutabakatComparisonResource\Pages;

use App\Enums\PaymentMethodEnum;
use App\Filament\Admin\Resources\ParkSessionResource;
use App\Models\ParkSession;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Visio\mutabakat\Enums\PaymentTypeEnum;
use Visio\mutabakat\Models\HgsParkTransaction;
use Visio\mutabakat\Models\Mutabakat;
use Visio\mutabakat\Resources\MutabakatComparisonResource;

class SessionComparison extends Page implements HasInfolists, HasTable
{
    use InteractsWithInfolists, InteractsWithTable;

    protected static string $resource = MutabakatComparisonResource::class;

    protected static string $view = 'mutabakat::pages.session-comparison';

    public Mutabakat $record;

    public Collection $hgsTransactions;

    public function mount(Mutabakat $record): void
    {
        $this->record = $record;
        $this->hgsTransactions = HgsParkTransaction::getByParkAndProvisionDate(
            $this->record->park_id,
            $this->record->provision_date
        );
    }

    public function getTitle(): string
    {
        return "Session Karşılaştırma - {$this->record->parent_parking_name} ({$this->record->provision_date->format('d.m.Y')})";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ParkSession::queryForSessionComparison($this->record->park_id, $this->record->provision_date?->toDateString())
                    ->with('payment')
            )
            ->columns([
                Tables\Columns\TextColumn::make('plate_txt')
                    ->label('Plaka')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ParkSession $record): string => route('filament.admin.resources.park-sessions.index', ['tableSearch' => $record->plate_txt]))
                    ->summarize(Tables\Columns\Summarizers\Count::make()->label('Kayıt')),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Ödeme Türü')
                    ->getStateUsing(function (ParkSession $record) {
                        $payment = $record->payment;
                        if (! $payment || ! $payment->service_id) {
                            return '-';
                        }

                        if ($payment->service_id instanceof PaymentMethodEnum) {
                            return $payment->service_id->getPaymentType();
                        }
                        if (is_numeric($payment->service_id)) {
                            $paymentMethod = PaymentMethodEnum::tryFrom((int) $payment->service_id);

                            return $paymentMethod?->getPaymentType() ?? '-';
                        }

                        return '-';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PaymentTypeEnum::HGS->value => 'success',
                        PaymentTypeEnum::POS->value => 'info',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('entry_at')
                    ->label('Giriş Tarihi')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('exit_at')
                    ->label('Çıkış Zamanı')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, ParkSession $record) {
                        if (! $record->exit_at instanceof \Carbon\Carbon) {
                            return '-';
                        }
                        $exit_at_carbon = \Carbon\Carbon::parse($record->exit_at);
                        $format = $record->entry_at->isSameDay($exit_at_carbon) ? 'H:i' : 'Y-m-d H:i';
                        $exitTime = $exit_at_carbon->format($format);
                        $duration = $record->entry_at->diffForHumans($exit_at_carbon, true);

                        return "<div>{$exitTime}</div><div style='font-size: 0.8em; color: gray;'>({$duration})</div>";
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Ödeme Tutarı')
                    ->money('TRY')
                    ->sortable()
                    ->alignCenter()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')->label('Toplam')),
                Tables\Columns\TextColumn::make('hgs_amount')
                    ->label('HGS Tutarı')
                    ->getStateUsing(function (ParkSession $record) {
                        if ($this->isNonHgsPayment($record)) {
                            return 0;
                        }

                        return Mutabakat::findMatchingHgsTransaction($record, $this->hgsTransactions)?->amount ?? 0;
                    })
                    ->money('TRY')
                    ->alignCenter()
                    ->color(function (ParkSession $record) {
                        if ($this->isNonHgsPayment($record)) {
                            return 'gray';
                        }

                        return null;
                    }),
                Tables\Columns\TextColumn::make('match_status')
                    ->label('Sonuç')
                    ->getStateUsing(function (ParkSession $record) {
                        if ($this->isNonHgsPayment($record)) {
                            return 'Eşleşti';
                        }
                        $matchingHgs = Mutabakat::findMatchingHgsTransaction($record, $this->hgsTransactions);
                        if (! $matchingHgs) {
                            return 'Oturum Detayına Git';
                        }
                        $hgsAmount = $matchingHgs->amount ?? 0;
                        $difference = abs($record->amount - $hgsAmount);

                        return $difference < 1 ? 'Eşleşti' : 'Oturum Detayına Git';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Eşleşti' ? 'success' : 'info')
                    ->icon(fn (string $state) => $state === 'Eşleşti' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-top-right-on-square')
                    ->url(function (ParkSession $record): ?string {
                        if ($this->isNonHgsPayment($record)) {
                            return null;
                        }

                        $matchingHgs = Mutabakat::findMatchingHgsTransaction($record, $this->hgsTransactions);
                        if ($matchingHgs && abs($record->amount - ($matchingHgs->amount ?? 0)) < 1) {
                            return null;
                        }

                        $entryDate = $record->entry_at?->format('Y-m-d');
                        $exitDate = $record->exit_at?->format('Y-m-d');

                        return ParkSessionResource::getUrl('index').'?'.http_build_query([
                            'tableFilters' => [
                                'park_id' => [
                                    'values' => [$this->record->park_id],
                                ],
                                'entry_at' => [
                                    'entry_from' => $entryDate,
                                    'entry_until' => $entryDate,
                                ],
                                'exit_at' => [
                                    'exit_from' => $exitDate,
                                    'exit_until' => $exitDate,
                                ],
                            ],
                            'tableSearch' => $record->plate_txt,
                        ]);
                    })
                    ->openUrlInNewTab()
                    ->alignCenter(),
            ])
            ->defaultSort('exit_at', 'desc');
    }

    private function isNonHgsPayment(ParkSession $session): bool
    {
        $payment = $session->payment;
        if (! $payment || ! $payment->service_id) {
            return false;
        }

        if ($payment->service_id instanceof PaymentMethodEnum) {
            return ! in_array($payment->service_id, [PaymentMethodEnum::HGS, PaymentMethodEnum::HGS_BACKEND]);
        }

        if (is_numeric($payment->service_id)) {
            $paymentMethod = PaymentMethodEnum::tryFrom((int) $payment->service_id);

            return $paymentMethod && ! in_array($paymentMethod, [PaymentMethodEnum::HGS, PaymentMethodEnum::HGS_BACKEND]);
        }

        return false;
    }

    public function summaryInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('Özet Bilgiler')
                    ->schema([]),
            ]);
    }
}
