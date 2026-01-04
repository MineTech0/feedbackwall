<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ModerationWebhookController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required',
            'decision' => 'required|in:allow,flag,block',
            'reason' => 'nullable',
        ]);

        $feedback = Feedback::find($validated['id']);

        if (!$feedback) {
            // If feedback is not found, we probably shouldn't return 404 to the webhook 
            // as it might retry. 200 is safer to acknowledge receipt.
            // But strict implementation might say 404. I'll return 404 for now.
            return response()->json(['status' => 'error', 'message' => 'Feedback not found'], 404);
        }

        $actionMap = [
            'allow' => 'publish',
            'flag' => 'pending', 
            'block' => 'reject',
        ];

        $action = $actionMap[$validated['decision']];
        
        // Encode reason if it's an array
        $reason = $validated['reason'] ?? null;
        if (is_array($reason)) {
            $reason = json_encode($reason);
        }

        $this->feedbackService->moderateFeedback(
            $feedback,
            null, // System user
            $action,
            $reason
        );

        return response()->json(['status' => 'ok']);
    }
}

