<?php

namespace Modules\Library\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Filament\Resources\BookResource\Pages;
use Modules\Library\Models\Book;
use Modules\Library\Models\Author;
use Modules\Library\Models\Category;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    // protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Library Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('isbn')
                            ->label('ISBN')
                            ->maxLength(20),
                        Forms\Components\Select::make('author_id')
                            ->label('Author')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Publication Details')
                    ->schema([
                        Forms\Components\TextInput::make('publisher')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('publication_year')
                            ->numeric()
                            ->minValue(1000)
                            ->maxValue(date('Y')),
                        Forms\Components\TextInput::make('pages')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(100)
                            ->helperText('Physical location in the library'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('total_copies')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\TextInput::make('available_copies')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1),
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'borrowed' => 'Borrowed',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('available')
                            ->required(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()
                            ->directory('library/covers')
                            ->maxSize(2048),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-book.png')),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('author.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('available_copies')
                    ->label('Available')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_copies')
                    ->label('Total')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'borrowed',
                        'danger' => 'maintenance',
                    ]),
                Tables\Columns\TextColumn::make('publication_year')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('author')
                    ->relationship('author', 'name'),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'borrowed' => 'Borrowed',
                        'maintenance' => 'Maintenance',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'view' => Pages\ViewBook::route('/{record}'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
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
        return auth()->user()?->can('view_any_library_books') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_library_books') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_library_books') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_library_books') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_library_books') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_library_books') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force_delete_library_books') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force_delete_any_library_books') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore_library_books') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore_any_library_books') ?? false;
    }
}
