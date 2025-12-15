<?php

namespace Visiosoft\Mutabakat\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinanceAgreementEnum: string implements HasLabel, HasColor
{
    case Waiting = 'waiting';
    case Done = 'done';
    case InProgress = 'in_progress';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Waiting => 'Bekliyor',
            self::Done => 'Tamamlandı',
            self::InProgress => 'İşlemde',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Waiting => 'warning',
            self::Done => 'success',
            self::InProgress => 'info',
        };
    }
}
