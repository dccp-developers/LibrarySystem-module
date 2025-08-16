<?php

namespace Modules\Library\Filament\Resources\BorrowRecordResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\BorrowRecordResource;

class EditBorrowRecord extends EditRecord
{
    protected static string $resource = BorrowRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Handle book availability when status changes
        $originalStatus = $this->record->getOriginal('status');
        $newStatus = $this->record->status;

        if ($originalStatus !== $newStatus) {
            if ($originalStatus === 'borrowed' && $newStatus === 'returned') {
                $this->record->book->increment('available_copies');
            } elseif ($originalStatus === 'returned' && $newStatus === 'borrowed') {
                $this->record->book->decrement('available_copies');
            }
        }
    }
}
