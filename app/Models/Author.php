<?php

declare(strict_types=1);

namespace Modules\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'library_authors';

    protected $fillable = [
        'name',
        'biography',
        'birth_date',
        'nationality',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    protected static function newFactory()
    {
        return \Modules\Library\database\factories\AuthorFactory::new();
    }
}
