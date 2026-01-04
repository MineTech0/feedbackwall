<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_public',
        'archived_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
