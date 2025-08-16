<?php

namespace Modules\Library\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Filament\Resources\BorrowRecordResource\Pages;
use Modules\Library\Models\Book;
use Modules\Library\Models\BorrowRecord;

class BorrowRecordResource extends Resource
{
    protected static ?string $model = BorrowRecord::class;

    // protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Library Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Borrow Records';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Borrow Information')
                    ->schema([
                        Forms\Components\Select::make('book_id')
                            ->label('Book')
                            ->relationship('book', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Book $record): string => "{$record->title} - {$record->author->name}"),
                        Forms\Components\Select::make('user_id')
                            ->label('Borrower')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'borrowed' => 'Borrowed',
                                'returned' => 'Returned',
                                'lost' => 'Lost',
                            ])
                            ->default('borrowed')
                            ->required()
                            ->reactive(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('borrowed_at')
                            ->default(now())
                            ->required(),
                        Forms\Components\DateTimePicker::make('due_date')
                            ->default(now()->addDays(14))
                            ->required(),
                        Forms\Components\DateTimePicker::make('returned_at')
                            ->visible(fn (Forms\Get $get): bool => $get('status') === 'returned'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('fine_amount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('book.title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),
                Tables\Columns\TextColumn::make('book.author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Borrower')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => fn ($state, $record) => $state === 'borrowed' && !$record->isOverdue(),
                        'danger' => fn ($state, $record) => $state === 'borrowed' && $record->isOverdue(),
                        'success' => 'returned',
                        'danger' => 'lost',
                    ]),
                Tables\Columns\TextColumn::make('borrowed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                Tables\Columns\TextColumn::make('returned_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('fine_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(fn (BorrowRecord $record): string => 
                        $record->isOverdue() ? (string) $record->days_overdue : '-'
                    )
                    ->color(fn ($state) => $state !== '-' ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'borrowed' => 'Borrowed',
                        'returned' => 'Returned',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('status', 'borrowed')
                        ->where('due_date', '<', now()))
                    ->label('Overdue Books'),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('return')
                    ->label('Mark as Returned')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (BorrowRecord $record): bool => $record->status === 'borrowed')
                    ->action(function (BorrowRecord $record): void {
                        $record->update([
                            'status' => 'returned',
                            'returned_at' => now(),
                        ]);
                        
                        // Update book availability
                        $record->book->increment('available_copies');
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('borrowed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBorrowRecords::route('/'),
            'create' => Pages\CreateBorrowRecord::route('/create'),
            'view' => Pages\ViewBorrowRecord::route('/{record}'),
            'edit' => Pages\EditBorrowRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_library_borrow_records') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_library_borrow_records') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_library_borrow_records') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_library_borrow_records') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_library_borrow_records') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_library_borrow_records') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force_delete_library_borrow_records') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force_delete_any_library_borrow_records') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore_library_borrow_records') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore_any_library_borrow_records') ?? false;
    }
}
