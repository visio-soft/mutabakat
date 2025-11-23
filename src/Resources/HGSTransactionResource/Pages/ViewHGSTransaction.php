<?php

namespace Visiosoft\Mutabakat\Resources\HGSTransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Visiosoft\Mutabakat\Resources\HGSTransactionResource;

class ViewHGSTransaction extends ViewRecord
{
    protected static string $resource = HGSTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
