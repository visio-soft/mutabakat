<?php

namespace Visiosoft\Mutabakat\Resources\HGSTransactionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Visiosoft\Mutabakat\Resources\HGSTransactionResource;

class ListHGSTransactions extends ListRecords
{
    protected static string $resource = HGSTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
