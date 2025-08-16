<?php

namespace Modules\Library\Filament\Resources\BookResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\BookResource;

class EditBook extends EditRecord
{
    protected static string $resource = BookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
