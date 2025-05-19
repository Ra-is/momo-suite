<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Rais\MomoSuite\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('momo')->check()) {
            return redirect()->route('momo.dashboard');
        }
        return view('momo-suite::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);



        if (Auth::guard('momo')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('momo.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('momo')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('momo.login');
    }
}
