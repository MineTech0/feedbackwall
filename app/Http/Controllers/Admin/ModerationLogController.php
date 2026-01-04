<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModerationLog;
use Inertia\Inertia;

class ModerationLogController extends Controller
{
    public function index()
    {
        $logs = ModerationLog::with(['user', 'feedback.board'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Admin/ModerationLogs', [
            'logs' => $logs
        ]);
    }
}
