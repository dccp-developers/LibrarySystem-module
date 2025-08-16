<?php

namespace Modules\Library\Filament\Resources\BorrowRecordResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\BorrowRecordResource;

class CreateBorrowRecord extends CreateRecord
{
    protected static string $resource = BorrowRecordResource::class;

    protected function afterCreate(): void
    {
        // Decrease available copies when a book is borrowed
        if ($this->record->status === 'borrowed') {
            $this->record->book->decrement('available_copies');
        }
    }
}
