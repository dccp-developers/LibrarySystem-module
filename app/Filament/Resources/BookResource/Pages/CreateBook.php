<?php

namespace Modules\Library\Filament\Resources\BookResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\BookResource;

class CreateBook extends CreateRecord
{
    protected static string $resource = BookResource::class;
}
