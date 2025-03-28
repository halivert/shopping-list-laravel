<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    public function login(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $exists = !!User::query()->firstWhere('email', $googleUser->getEmail());

        User::query()->upsert([
            'name' => $googleUser->getNickname(),
            'email' => $googleUser->getEmail(),
        ], 'email', ['name']);

        $user = User::query()->firstWhere('email', $googleUser->getEmail());

        if (today() <= '2025-05-01') {
            $user->is_early_adopter = true;
            $user->save();
        }

        Auth::login($user);

        if (!$exists) {
            event(new Registered($user));
        }

        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }
}
