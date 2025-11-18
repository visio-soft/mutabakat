<?php

namespace Visiosoft\Mutabakat\Resources\HGSTransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Visiosoft\Mutabakat\Resources\HGSTransactionResource;

class EditHGSTransaction extends EditRecord
{
    protected static string $resource = HGSTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
