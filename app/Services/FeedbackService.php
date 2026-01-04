<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Feedback;
use App\Models\FeedbackVote;
use App\Models\ModerationLog;
use App\Models\User;
use App\Support\Fingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackService
{
    public function __construct(
        protected ContentModerationService $moderator
    ) {}

    public function createFeedback(Board $board, string $content, Request $request): ?Feedback
    {
        $fingerprint = Fingerprint::fromRequest($request);
        
        // Initial creation is optimistic
        $feedback = Feedback::create([
            'board_id' => $board->id,
            'content' => $content,
            'moderation_state' => 'published',
            'creator_fingerprint' => $fingerprint,
        ]);

        // Async moderation
        $this->moderator->submit($feedback);

        return $feedback;
    }

    public function toggleVote(Feedback $feedback, Request $request): int
    {
        if ($feedback->board->archived_at) {
            return $feedback->votes_count;
        }

        $fingerprint = Fingerprint::fromRequest($request);

        return DB::transaction(function () use ($feedback, $fingerprint) {
            $existing = FeedbackVote::where('feedback_id', $feedback->id)
                ->where('voter_fingerprint', $fingerprint)
                ->first();

            if ($existing) {
                $existing->delete();
                $feedback->decrement('votes_count');
            } else {
                FeedbackVote::create([
                    'feedback_id' => $feedback->id,
                    'voter_fingerprint' => $fingerprint,
                ]);
                $feedback->increment('votes_count');
            }
            
            return $feedback->refresh()->votes_count;
        });
    }

    public function moderateFeedback(Feedback $feedback, ?User $user, string $action, ?string $reason): void
    {
        $stateMap = [
            'publish' => 'published',
            'reject' => 'rejected',
            'pending' => 'pending_review',
        ];

        $feedback->update(['moderation_state' => $stateMap[$action]]);

        ModerationLog::create([
            'user_id' => $user?->id,
            'feedback_id' => $feedback->id,
            'action' => $action,
            'reason' => $reason,
        ]);
    }

    public function getFeedbackForBoardAdmin(Board $board)
    {
        return $board->feedback()
            ->withCount('votes')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }
}

