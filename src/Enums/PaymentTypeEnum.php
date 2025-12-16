<?php

namespace Visio\mutabakat\Enums;

enum PaymentTypeEnum: string
{
    case HGS = 'HGS';
    case POS = 'POS';

    public function getLabel(): string
    {
        return match ($this) {
            self::HGS => 'HGS',
            self::POS => 'POS',
        };
    }

    public static function getOptions(): array
    {
        return [
            self::HGS->value => self::HGS->getLabel(),
            self::POS->value => self::POS->getLabel(),
        ];
    }
}
