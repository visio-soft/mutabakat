@if($sheetType === 'detail' || $sheetType === 'monthly_detail')
    @php
        $startDate = $summary['start_date'] ?? null;
        $endDate = $summary['end_date'] ?? null;
        $dateRange = '';
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $dateRange = $startDate;
            } else {
                $dateRange = $startDate . ' - ' . $endDate;
            }
        }
    @endphp
    <table>
        <thead>
            <tr style="height: 45;">
                <th colspan="8"
                    style="font-weight: bold; background-color: #2C5282; color: white; text-align: center; font-size: 14px; vertical-align: middle;">
                    TÜM İŞLEMLER
                </th>
            </tr>
            @if($dateRange)
                <tr style="height: 35;">
                    <th colspan="8"
                        style="font-weight: normal; background-color: #2C5282; color: white; text-align: center; font-size: 12px; vertical-align: middle;">
                        {{ $dateRange }}
                    </th>
                </tr>
            @endif
            <tr style="height: 30;">
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">HGS Rapor Tarihi
                </th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 140px;">Giriş Tarihi
                </th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 140px;">Çıkış Tarihi
                </th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 200px;">Otopark</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">Plaka</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 100px;">Tutar (TL)</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">Ödeme Yöntemi
                </th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 140px;">Ödeme Tarihi
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                @php
                    $amount = (float) str_replace(['.', ','], ['', '.'], $transaction['amount'] ?? '0');
                    $amountColor = $amount < 0 ? '#DC3545' : '#27AE60';
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $transaction['provision_date'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $transaction['entry_date'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $transaction['exit_date'] ?? '-' }}</td>
                    <td style="text-align: left; font-weight: bold; padding-left: 10px; color: #2C5282;">
                        {{ $transaction['parking_name'] ?? '-' }}
                    </td>
                    <td style="text-align: center; font-weight: bold; color: #2980B9;">{{ $transaction['plate'] ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold; padding-right: 10px; color: {{ $amountColor }};">
                        {{ $transaction['amount'] ?? '0,00' }}
                    </td>
                    <td style="text-align: center; font-weight: bold;">{{ $transaction['payment_method'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $transaction['payment_date'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #6C757D; font-style: italic;">Gösterilecek
                        veri bulunamadı.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@elseif($sheetType === 'daily')
    @php
        $startDate = $summary['start_date'] ?? null;
        $endDate = $summary['end_date'] ?? null;
        $dateRange = '';
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $dateRange = $startDate;
            } else {
                $dateRange = $startDate . ' - ' . $endDate;
            }
        }

        $hasPos = collect($dailySummary)->sum('pos_count') > 0;
    @endphp
    <table>
        <thead>
            <tr style="height: 45;">
                <th colspan="6"
                    style="font-weight: bold; background-color: #2C5282; color: white; text-align: center; font-size: 14px; vertical-align: middle;">
                    GÜN BAZLI DETAYLAR
                </th>
            </tr>
            @if($dateRange)
                <tr style="height: 35;">
                    <th colspan="6"
                        style="font-weight: normal; background-color: #2C5282; color: white; text-align: center; font-size: 12px; vertical-align: middle;">
                        {{ $dateRange }}
                    </th>
                </tr>
            @endif
            <tr style="height: 30;">
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 100px;">Tarih</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 180px;">İşlem Adı</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 100px;">İşlem Adedi</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">Tutar</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">Komisyon</th>
                <th style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 120px;">Net</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalHgsCount = 0;
                $totalHgsAmount = 0;
                $totalHgsCommission = 0;
                $totalHgsNet = 0;
                $totalPosCount = 0;
                $totalPosAmount = 0;
            @endphp
            @forelse($dailySummary as $day)
                @php
                    $totalHgsCount += $day['hgs_count'];
                    $totalHgsAmount += $day['hgs_total'];
                    $totalHgsCommission += $day['hgs_commission'];
                    $totalHgsNet += $day['hgs_net'];
                    $totalPosCount += $day['pos_count'];
                    $totalPosAmount += $day['pos_total'];
                @endphp
                @if($day['hgs_count'] > 0)
                    <tr>
                        <td style="text-align: center;">{{ $day['date'] }}</td>
                        <td style="text-align: left; padding-left: 10px; font-weight: bold;">HGS PARK GEÇİŞ ÜCRETİ</td>
                        <td style="text-align: center;">{{ $day['hgs_count'] }}</td>
                        <td style="text-align: right; padding-right: 10px;">{{ number_format($day['hgs_total'], 2, ',', '.') }} ₺</td>
                        <td style="text-align: right; padding-right: 10px;">{{ number_format($day['hgs_commission'], 2, ',', '.') }} ₺</td>
                        <td style="text-align: right; padding-right: 10px; font-weight: bold; color: #27AE60;">{{ number_format($day['hgs_net'], 2, ',', '.') }} ₺</td>
                    </tr>
                @endif
                @if($day['pos_count'] > 0)
                    <tr style="background-color: #F8F9FA;">
                        <td style="text-align: center;">{{ $day['date'] }}</td>
                        <td style="text-align: left; padding-left: 10px; font-weight: bold;">POS PARK GEÇİŞ ÜCRETİ</td>
                        <td style="text-align: center;">{{ $day['pos_count'] }}</td>
                        <td style="text-align: right; padding-right: 10px;">{{ number_format($day['pos_total'], 2, ',', '.') }} ₺</td>
                        <td style="text-align: right; padding-right: 10px; color: #6C757D;">-</td>
                        <td style="text-align: right; padding-right: 10px; font-weight: bold; color: #27AE60;">{{ number_format($day['pos_total'], 2, ',', '.') }} ₺</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #6C757D; font-style: italic;">Gösterilecek veri bulunamadı.</td>
                </tr>
            @endforelse

            <tr>
                <td colspan="6" style="height: 10px;"></td>
            </tr>

            @if($totalHgsCount > 0)
                <tr style="background-color: #E3F2FD; font-weight: bold;">
                    <td style="text-align: center;"></td>
                    <td style="text-align: left; padding-left: 10px; color: #1565C0;">HGS TOPLAM</td>
                    <td style="text-align: center;">{{ $totalHgsCount }}</td>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($totalHgsAmount, 2, ',', '.') }} ₺</td>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($totalHgsCommission, 2, ',', '.') }} ₺</td>
                    <td style="text-align: right; padding-right: 10px; color: #27AE60;">{{ number_format($totalHgsNet, 2, ',', '.') }} ₺</td>
                </tr>
            @endif

            @if($totalPosCount > 0)
                <tr style="background-color: #E8F5E9; font-weight: bold;">
                    <td style="text-align: center;"></td>
                    <td style="text-align: left; padding-left: 10px; color: #2E7D32;">POS TOPLAM</td>
                    <td style="text-align: center;">{{ $totalPosCount }}</td>
                    <td style="text-align: right; padding-right: 10px;">{{ number_format($totalPosAmount, 2, ',', '.') }} ₺</td>
                    <td style="text-align: right; padding-right: 10px; color: #6C757D;">-</td>
                    <td style="text-align: right; padding-right: 10px; color: #27AE60;">{{ number_format($totalPosAmount, 2, ',', '.') }} ₺</td>
                </tr>
            @endif

            <tr>
                <td colspan="6" style="height: 20px;"></td>
            </tr>

            <tr>
                <td colspan="6" style="text-align: center; font-size: 11px; color: #6C757D; font-style: italic;">
                    Rapor Oluşturma Tarihi: {{ now()->format('d.m.Y H:i') }}
                </td>
            </tr>
        </tbody>
    </table>
@elseif($sheetType === 'summary' || $sheetType === 'monthly_summary')
    @php
        $startDate = $summary['start_date'] ?? null;
        $endDate = $summary['end_date'] ?? null;
        $dateRange = '';
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $dateRange = $startDate;
            } else {
                $dateRange = $startDate . ' - ' . $endDate;
            }
        }
    @endphp
    <table>
        <thead>
            <tr style="height: 45;">
                <th colspan="3"
                    style="font-weight: bold; background-color: #2C5282; color: white; text-align: center; font-size: 14px; vertical-align: middle;">
                    HGS / POS MUTABAKAT RAPORU ÖZETİ
                </th>
            </tr>
            @if($dateRange)
                <tr style="height: 35;">
                    <th colspan="3"
                        style="font-weight: normal; background-color: #2C5282; color: white; text-align: center; font-size: 12px; vertical-align: middle;">
                        {{ $dateRange }}
                    </th>
                </tr>
            @endif
            <tr style="height: 30;">
                <th
                    style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 250px; vertical-align: middle;">
                    Açıklama</th>
                <th
                    style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 200px; vertical-align: middle;">
                    Değer</th>
                <th
                    style="font-weight: bold; background-color: #34495E; color: white; text-align: center; width: 100px; vertical-align: middle;">
                    Oran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $hgsTotal = $summary['hgs_total'] ?? 0;
                $hgsCount = $summary['hgs_count'] ?? 0;
                $posTotal = $summary['pos_total'] ?? 0;
                $posCount = $summary['pos_count'] ?? 0;
                $grandTotal = $summary['grand_total'] ?? ($hgsTotal + $posTotal);
                $hgsPercent = $summary['hgs_percent'] ?? 0;
                $posPercent = $summary['pos_percent'] ?? 0;
                $transactionCount = $hgsCount + $posCount;
            @endphp

            <tr style="background-color: #E3F2FD;">
                <td colspan="3" style="font-weight: bold; text-align: left; padding: 15px; color: #1565C0;">💳 ÖDEME YÖNTEMİ
                    DAĞILIMI</td>
            </tr>
            <tr style="background-color: #FFF8E1;">
                <td style="padding-left: 25px; font-weight: bold;">HGS Ödemeleri</td>
                <td style="text-align: center; font-weight: bold; color: #F57C00;">
                    {{ number_format($hgsTotal, 2, ',', '.') }} TL ({{ number_format($hgsCount) }} işlem)
                </td>
                <td style="text-align: center; font-weight: bold; color: #F57C00;">{{ $hgsPercent }}%</td>
            </tr>
            <tr style="background-color: #E8F5E9;">
                <td style="padding-left: 25px; font-weight: bold;">POS Ödemeleri</td>
                <td style="text-align: center; font-weight: bold; color: #2E7D32;">
                    {{ number_format($posTotal, 2, ',', '.') }} TL ({{ number_format($posCount) }} işlem)
                </td>
                <td style="text-align: center; font-weight: bold; color: #2E7D32;">{{ $posPercent }}%</td>
            </tr>
            <tr style="background-color: #E1F5FE;">
                <td style="padding-left: 25px; font-weight: bold;">GENEL TOPLAM</td>
                <td style="text-align: center; font-weight: bold; color: #0277BD;">
                    {{ number_format($grandTotal, 2, ',', '.') }} TL ({{ number_format($transactionCount) }} işlem)
                </td>
                <td style="text-align: center; font-weight: bold; color: #0277BD;">100%</td>
            </tr>

            <tr>
                <td colspan="3" style="height: 15px;"></td>
            </tr>

            @php
                $totalAmount = 0;
                $positiveAmount = 0;
                $negativeAmount = 0;
                $positiveCount = 0;
                $negativeCount = 0;

                foreach ($transactions as $transaction) {
                    $amount = $transaction['amount_raw'] ?? (float) str_replace(['.', ','], ['', '.'], $transaction['amount'] ?? '0');
                    $totalAmount += $amount;
                    if ($amount > 0) {
                        $positiveAmount += $amount;
                        $positiveCount++;
                    } elseif ($amount < 0) {
                        $negativeAmount += $amount;
                        $negativeCount++;
                    }
                }
            @endphp

            <tr style="background-color: #F8F9FA;">
                <td colspan="3" style="font-weight: bold; text-align: left; padding: 15px; color: #2C5282;">📊 İŞLEM
                    DETAYLARI</td>
            </tr>
            <tr>
                <td style="padding-left: 25px;">Toplam İşlem Sayısı</td>
                <td style="text-align: center; font-weight: bold; color: #27AE60;">
                    {{ number_format($transactionCount) }}
                </td>
                <td style="text-align: center;">100%</td>
            </tr>
            <tr style="background-color: #F8F9FA;">
                <td style="padding-left: 20px;">Başarılı İşlem Sayısı</td>
                <td style="text-align: center; font-weight: bold; color: #27AE60;">{{ number_format($positiveCount) }}</td>
                <td style="text-align: center;">
                    {{ $transactionCount > 0 ? number_format(($positiveCount / $transactionCount) * 100, 1) : 0 }}%
                </td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">İptal Edilen İşlem Sayısı</td>
                <td style="text-align: center; font-weight: bold; color: #DC3545;">{{ number_format($negativeCount) }}</td>
                <td style="text-align: center;">
                    {{ $transactionCount > 0 ? number_format(($negativeCount / $transactionCount) * 100, 1) : 0 }}%
                </td>
            </tr>

            <tr>
                <td colspan="3" style="height: 10px;"></td>
            </tr>
            <tr style="background-color: #F8F9FA;">
                <td colspan="3" style="font-weight: bold; text-align: left; padding: 10px; color: #2C5282;">💰 FİNANSAL
                    BİLGİLER</td>
            </tr>
            <tr style="background-color: #E8F5E9;">
                <td style="padding-left: 20px; font-weight: bold;">Toplam Gelir</td>
                <td style="text-align: center; font-weight: bold; color: #27AE60; ">
                    {{ number_format($positiveAmount, 2, ',', '.') }} TL
                </td>
                <td style="text-align: center; font-weight: bold;">100%</td>
            </tr>
            <tr style="background-color: #FFEBEE;">
                <td style="padding-left: 20px;">Toplam İptal/İade</td>
                <td style="text-align: center; font-weight: bold; color: #DC3545;">
                    {{ number_format($negativeAmount, 2, ',', '.') }} TL
                </td>
                <td style="text-align: center;">
                    {{ $positiveAmount != 0 ? number_format((abs($negativeAmount) / $positiveAmount) * 100, 1) : 0 }}%
                </td>
            </tr>

            <tr>
                <td style="padding-left: 20px; font-weight: bold;">Net Toplam Tutar</td>
                <td style="text-align: center; font-weight: bold; color: #27AE60;">
                    {{ number_format($totalAmount, 2, ',', '.') }} TL
                </td>
                <td style="text-align: center;">
                    {{ $positiveAmount != 0 ? number_format(($totalAmount / $positiveAmount) * 100, 1) : 0 }}%
                </td>
            </tr>

            @php
                $hgsCommission = 0.0497;
                $hgsCommissionAmount = $hgsTotal * $hgsCommission;
                $netHgsTransfer = $hgsTotal - $hgsCommissionAmount;
            @endphp

            <tr>
                <td colspan="3" style="height: 10px;"></td>
            </tr>
            <tr style="background-color: #FFF3CD;">
                <td style="padding-left: 20px; font-weight: bold;">HGS Komisyonu (Sadece HGS için)</td>
                <td style="text-align: center; font-weight: bold; color: #E67E22;">
                    {{ number_format($hgsCommissionAmount, 2, ',', '.') }} TL
                </td>
                <td style="text-align: center;">{{ $hgsCommission * 100 }}%</td>
            </tr>
            <tr style="background-color: #D4EDDA;">
                <td style="padding-left: 20px; font-weight: bold;">Net HGS Transfer Tutarı</td>
                <td style="text-align: center; font-weight: bold; color: #155724;">
                    {{ number_format($netHgsTransfer, 2, ',', '.') }} TL
                </td>
                <td style="text-align: center; font-weight: bold;">-</td>
            </tr>

            @if($sheetType === 'monthly_summary')
                <tr>
                    <td colspan="3" style="height: 10px;"></td>
                </tr>
                <tr style="background-color: #FFF3CD;">
                    <td style="padding-left: 20px; font-weight: bold;">📅 Aylık Rapor</td>
                    <td style="text-align: center; font-weight: bold;">{{ $transactionCount }} İşlem</td>
                    <td style="text-align: center;">-</td>
                </tr>
            @endif

            <tr>
                <td colspan="3" style="height: 20px;"></td>
            </tr>

            <tr>
                <td colspan="3" style="text-align: center; font-size: 11px; color: #6C757D; font-style: italic;">
                    Rapor Oluşturma Tarihi: {{ now()->format('d.m.Y H:i') }}
                </td>
            </tr>
        </tbody>
    </table>
@endif