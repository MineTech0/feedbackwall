<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Board;
use App\Services\FeedbackService;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function store(StoreFeedbackRequest $request, $slug)
    {
        $board = Board::where('slug', $slug)->firstOrFail();

        if ($board->archived_at) {
            return back()->withErrors(['content' => __('messages.feedback.error')]);
        }

        $feedback = $this->feedbackService->createFeedback(
            $board, 
            $request->validated('content'), 
            $request
        );

        if (!$feedback) {
             // Rejected content
            return back()->withErrors(['content' => __('messages.feedback.error')]);
        }

        return back()->with('success', __('messages.feedback.success'));
    }
}
