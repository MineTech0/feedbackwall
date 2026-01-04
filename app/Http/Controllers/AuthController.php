<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['login' => 'Login failed.']);
        }

        // Check if user exists
        $user = User::where('google_id', $googleUser->id)->first();

        if ($user) {
            Auth::login($user);
            return redirect('/admin');
        }

        // Check if email already exists (merged account or manual add)
        $user = User::where('email', $googleUser->email)->first();
        if ($user) {
             $user->update(['google_id' => $googleUser->id]);
             Auth::login($user);
             return redirect('/admin');
        }

        // Check against allowed emails
        $allowedEmails = explode(',', config('app.admin_emails', env('ADMIN_EMAILS', '')));
        if (in_array($googleUser->email, $allowedEmails)) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'is_admin' => true,
                'password' => null,
            ]);
            Auth::login($user);
            return redirect('/admin');
        }

        return redirect('/')->withErrors(['email' => 'Unauthorized email.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
