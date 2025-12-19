<?php

namespace Visio\mutabakat\Resources\MutabakatParkResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Visio\mutabakat\Resources\MutabakatParkResource;

class ListMutabakatParks extends ListRecords
{
    protected static string $resource = MutabakatParkResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
