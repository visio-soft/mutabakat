<?php

namespace Visiosoft\Mutabakat\Resources\MutabakatResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Visiosoft\Mutabakat\Resources\MutabakatResource;

class EditMutabakat extends EditRecord
{
    protected static string $resource = MutabakatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
