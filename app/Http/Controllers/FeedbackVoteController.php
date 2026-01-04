<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\Request;

class FeedbackVoteController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function toggle(Request $request, Feedback $feedback)
    {
        $newCount = $this->feedbackService->toggleVote($feedback, $request);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
             return response()->json(['votes_count' => $newCount]);
        }

        return back();
    }
}
