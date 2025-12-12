<?php

namespace Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource\Pages;

use Visiosoft\Reconciliation\Resources\ReconciliationComparisonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReconciliationComparison extends EditRecord
{
    protected static string $resource = ReconciliationComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
