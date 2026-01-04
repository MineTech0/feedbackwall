<?php

namespace App\Services;

use App\Models\Feedback;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ContentModerationService
{

    public function submit(Feedback $feedback): void
    {
        $url = config('services.moderation.url');

        if (!$url) {
            Log::warning('Moderation service URL not configured.');
            return;
        }

        try {
            $callbackUrl = URL::signedRoute('moderation.callback');

            Http::post($url . '/moderate', [
                'id' => (string) $feedback->id,
                'text' => $feedback->content,
                'callback_url' => $callbackUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit feedback for moderation: ' . $e->getMessage());
        }
    }
}
