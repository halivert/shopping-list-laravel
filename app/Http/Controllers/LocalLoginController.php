<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LocalLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (!App::isLocal()) {
            abort(404);
        }

        $attrs = $request->validate([
            'email' => 'required|string|max:100|email'
        ]);

        $email = $attrs['email'];
        $exists = !!User::query()->firstWhere('email', $email);

        User::query()->upsert([
            'name' => "Test $email",
            'email' => $email,
        ], 'email', ['name']);

        $user = User::query()->firstWhere('email', $email);

        if (today() <= '2025-05-01') {
            $user->is_early_adopter = true;
            $user->save();
        }

        Auth::login($user);

        if (!$exists) {
            event(new Registered($user));
        }

        $request->session()->regenerate();

        return redirect()->intended('home');
    }
}
