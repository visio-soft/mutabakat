<?php

namespace Visiosoft\Mutabakat\Resources\MutabakatComparisonResource\Pages;

use Visiosoft\Mutabakat\Resources\MutabakatComparisonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMutabakatComparison extends EditRecord
{
    protected static string $resource = MutabakatComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
