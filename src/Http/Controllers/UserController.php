<?php

namespace Rais\MomoSuite\Http\Controllers;

use Illuminate\Http\Request;
use Rais\MomoSuite\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('momo-suite::dashboard.users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:momo_users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = $request->role ?? 'user';
        $permissions = $request->permissions ?? (
            $role === 'status_updater'
            ? ['transactions.view', 'transactions.update']
            : ['transactions.view']
        );

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'permissions' => $permissions,
        ]);

        return redirect()->route('momo.users.index')->with('success', 'User created successfully. Please share the password with them securely.');
    }

    public function updateCredentials(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_email' => ['required', 'string', 'email', 'max:255', 'unique:momo_users,email,' . $user->id],
            'new_password' => ['required', 'string', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(), 'confirmed'],
        ], [
            'new_password' => 'Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);

        $user->update([
            'email' => $request->new_email,
            'password' => Hash::make($request->new_password),
        ]);

        if ($request->logout) {
            auth()->logout();
            return redirect()->route('momo.login')->with('success', 'Credentials updated successfully. Please login with your new credentials.');
        }

        return back()->with('success', 'Credentials updated successfully.');
    }

    public function resetPassword(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot reset admin password.');
        }

        $password = Str::random(12);
        $user->update([
            'password' => Hash::make($password)
        ]);

        return back()->with('success', "User's password has been reset to: " . $password);
    }

    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot delete admin user.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }
}
