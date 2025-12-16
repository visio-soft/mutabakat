<?php

namespace Visio\mutabakat\Resources\MutabakatResource\Pages;

use Visio\mutabakat\Resources\MutabakatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
