<?php

namespace Visiosoft\Reconciliation\Resources\ReconciliationResource\Pages;

use Visiosoft\Reconciliation\Resources\ReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReconciliation extends EditRecord
{
    protected static string $resource = ReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
