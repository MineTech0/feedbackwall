<?php

namespace App\Support;

use Illuminate\Http\Request;

class Fingerprint
{
    public static function fromRequest(Request $request): string
    {
        $ip = $request->ip() ?? '0.0.0.0';
        $ua = $request->userAgent() ?? 'unknown';
        $secret = config('app.key');

        return hash('sha256', $ip . '|' . $ua . '|' . $secret);
    }
}

