<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateFeedbackRequest;
use App\Models\Board;
use App\Models\Feedback;
use App\Services\FeedbackService;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function index(Board $board)
    {
        return Inertia::render('Admin/Boards/Feedback', [
            'board' => $board,
            'feedback' => $this->feedbackService->getFeedbackForBoardAdmin($board)
        ]);
    }

    public function moderate(ModerateFeedbackRequest $request, Feedback $feedback)
    {
        $this->feedbackService->moderateFeedback(
            $feedback,
            $request->user(),
            $request->validated('action'),
            $request->validated('reason')
        );

        return back()->with('success', 'Feedback moderated.');
    }
}
