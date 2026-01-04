<?php

namespace App\Services;

use App\Models\Board;
use App\Models\FeedbackVote;
use App\Support\Fingerprint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BoardService
{
    public function getActiveBoards(): Collection
    {
        return Board::where('is_public', true)
            ->whereNull('archived_at')
            ->get();
    }

    public function getAllBoards(): Collection
    {
        return Board::orderBy('created_at', 'desc')->get();
    }

    public function getBoardBySlug(string $slug): Board
    {
        return Board::where('slug', $slug)->firstOrFail();
    }

    public function createBoard(array $data): Board
    {
        $slug = Str::slug($data['name']);
        
        $count = Board::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        return Board::create([
            ...$data,
            'slug' => $slug,
        ]);
    }

    public function updateBoard(Board $board, array $data): bool
    {
        return $board->update($data);
    }

    public function archiveBoard(Board $board): bool
    {
        return $board->update(['archived_at' => now()]);
    }

    public function getBoardFeedback(Board $board, string $sort, Request $request): array
    {
        $query = $board->feedback()
            ->where('moderation_state', 'published');
            
        if ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('votes_count', 'desc');
        }

        $feedback = $query->paginate(20)->withQueryString();

        $fingerprint = Fingerprint::fromRequest($request);
        $votedIds = FeedbackVote::where('voter_fingerprint', $fingerprint)
            ->whereIn('feedback_id', $feedback->pluck('id'))
            ->pluck('feedback_id');

        return [
            'feedback' => $feedback,
            'votedIds' => $votedIds,
        ];
    }
}

