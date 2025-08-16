<?php

namespace Modules\Library\Filament\Resources\BookResource\Pages;

use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Modules\Library\Imports\LibraryAccessionImport;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\BookResource;

class ListBooks extends ListRecords
{
    protected static string $resource = BookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Import Books')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->visible(fn () => auth()->user()?->can('import_library_books') ?? false)
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required()
                        ->columnSpanFull()
                        ->hint('Upload an Excel file with library accession data. Expected format: AccessionNumber, CallNumber, Author, Title, Publisher, Year, etc.')
                ])
                ->action(function (array $data) {
                    $file = $data['file'];
                    
                    try {
                        $import = new LibraryAccessionImport();
                        Excel::import($import, $file);
                        
                        $imported = $import->getImportedCount();
                        $skipped = $import->getSkippedCount();
                        $errors = $import->getErrors();
                        
                        $message = "Import completed! {$imported} books imported";
                        if ($skipped > 0) {
                            $message .= ", {$skipped} rows skipped";
                        }
                        
                        if (count($errors) > 0) {
                            $message .= ". Some errors occurred - check logs for details.";
                        }
                        
                        Notification::make()
                            ->title('Import Successful')
                            ->body($message)
                            ->success()
                            ->send();
                            
                        // Refresh the page to show new records
                        return redirect()->to(static::getResource()::getUrl('index'));
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
