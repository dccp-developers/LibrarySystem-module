<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'library_books';

    protected $fillable = [
        'title',
        'isbn',
        'author_id',
        'category_id',
        'publisher',
        'publication_year',
        'pages',
        'description',
        'cover_image',
        'total_copies',
        'available_copies',
        'location',
        'status',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'pages' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0 && $this->status === 'available';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'available' => 'success',
            'borrowed' => 'warning',
            'maintenance' => 'danger',
            default => 'gray',
        };
    }

    protected static function newFactory()
    {
        return \Modules\Library\database\factories\BookFactory::new();
    }
}
