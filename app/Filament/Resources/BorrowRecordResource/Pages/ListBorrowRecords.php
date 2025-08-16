<?php

namespace Modules\Library\Filament\Resources\BorrowRecordResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\BorrowRecordResource;

class ListBorrowRecords extends ListRecords
{
    protected static string $resource = BorrowRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
