<?php

namespace Visio\mutabakat\Resources\MutabakatComparisonResource\Pages;

use App\Enums\PaymentMethodEnum;
use App\Models\Payment;
use App\Filament\Admin\Resources\PaymentResource;
use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Visio\mutabakat\Models\Mutabakat;
use Visio\mutabakat\Resources\MutabakatComparisonResource;

class PaymentComparison extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable, InteractsWithInfolists;

    protected static string $resource = MutabakatComparisonResource::class;

    protected string $view = 'mutabakat::pages.payment-comparison';

    public Mutabakat $record;

    public function mount(Mutabakat $record): void
    {
        $this->record = $record;
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
                ->whereIn('service_id', [PaymentMethodEnum::HGS->value, PaymentMethodEnum::HGS_BACKEND->value])
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
               
                Tables\Filters\TernaryFilter::make('match_status')
                    ->label('Eşleşme Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Eşleşti')
                    ->falseLabel('Eşleşmedi')
                    ->queries(
                        true: function (Builder $query) {
                            // Sadece HGS ödemeleri ve park session'da mutabakat_is_matched = true olan
                            return $query->whereIn('service_id', [PaymentMethodEnum::HGS->value, PaymentMethodEnum::HGS_BACKEND->value])
                                ->whereHas('parkSession', fn ($q) => $q->where('mutabakat_is_matched', true));
                        },
                        false: function (Builder $query) {
                            // Sadece HGS ödemeleri ve park session'da mutabakat_is_matched = false olan
                            return $query->whereIn('service_id', [PaymentMethodEnum::HGS->value, PaymentMethodEnum::HGS_BACKEND->value])
                                ->whereHas('parkSession', fn ($q) => $q->where('mutabakat_is_matched', false));
                        },
                        blank: fn (Builder $query) => $query->whereIn('service_id', [PaymentMethodEnum::HGS->value, PaymentMethodEnum::HGS_BACKEND->value]),
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function getMatchStatusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('match_status')
            ->label('Sonuç')
            ->getStateUsing(function (Payment $record) {
                // Park session üzerinden mutabakat_is_matched kontrolü
                $session = $record->parkSession;
                if (!$session) {
                    return 'Ödeme Detayına Git';
                }
                
                return $session->mutabakat_is_matched ? 'Eşleşti' : 'Ödeme Detayına Git';
            })
            ->color(fn (string $state) => $state === 'Eşleşti' ? 'success' : 'info')
            ->icon(fn (string $state) => $state === 'Eşleşti' ? 'heroicon-o-check-circle' : 'heroicon-o-arrow-top-right-on-square')
            ->url(function (Payment $record, string $state): ?string {
                if ($state === 'Eşleşti') {
                    return null;
                }

                $session = $record->parkSession;
                if (! $session) {
                    return null;
                }

                $provisionDate = $this->record->provision_date->format('d/m/Y');
                $dateRange = $provisionDate.' - '.$provisionDate;

                return PaymentResource::getUrl('index').'?'.http_build_query([
                    'tableFilters' => [
                        'park_id' => [
                            'value' => [$this->record->park_id],
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
    
    public function summaryInfolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ödeme Özet Bilgileri')
                    ->schema([
                        Grid::make(4)
                            ->schema([]),
                    ]),
            ]);
    }
}
