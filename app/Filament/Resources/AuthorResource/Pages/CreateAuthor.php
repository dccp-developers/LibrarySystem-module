<?php

namespace Modules\Library\Filament\Resources\AuthorResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\AuthorResource;

class CreateAuthor extends CreateRecord
{
    protected static string $resource = AuthorResource::class;
}
