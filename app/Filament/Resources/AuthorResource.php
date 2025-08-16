<?php

namespace Modules\Library\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Filament\Resources\AuthorResource\Pages;
use Modules\Library\Models\Author;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    // protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Library Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('biography')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Birth Date'),
                Forms\Components\TextInput::make('nationality')
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('books_count')
                    ->counts('books')
                    ->label('Books Count'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'view' => Pages\ViewAuthor::route('/{record}'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
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
        return auth()->user()?->can('view_any_library_authors') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_library_authors') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_library_authors') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_library_authors') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_library_authors') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_any_library_authors') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force_delete_library_authors') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force_delete_any_library_authors') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore_library_authors') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore_any_library_authors') ?? false;
    }
}
