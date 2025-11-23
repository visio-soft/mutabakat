<?php

namespace Visiosoft\Mutabakat\Resources\MutabakatResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Visiosoft\Mutabakat\Resources\MutabakatResource;

class ListMutabakat extends ListRecords
{
    protected static string $resource = MutabakatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
