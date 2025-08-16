<?php

namespace Modules\Library\Filament\Resources\BorrowRecordResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Library\Filament\Resources\BorrowRecordResource;

class ViewBorrowRecord extends ViewRecord
{
    protected static string $resource = BorrowRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
