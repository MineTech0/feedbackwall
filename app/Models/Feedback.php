<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'board_id',
        'content',
        'moderation_state',
        'votes_count',
        'creator_fingerprint',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedbackVote::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }
}
