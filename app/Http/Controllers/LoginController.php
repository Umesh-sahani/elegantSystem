<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function index()
    {
        // testing only: seeded users shown in the Demo Login card
        $demoUsers = [
            ['role' => 'Admin', 'email' => 'admin@example.com', 'password' => '123456', 'icon' => 'bi-person-gear'],
            ['role' => 'User', 'email' => 'user@example.com', 'password' => '123456', 'icon' => 'bi-person'],
        ];

        return view('Auth.login', compact('demoUsers'));
    }

    public function authenticate(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect(route('login'))->withErrors($validator)->withInput($request->only('email'));
        }

        $remember = $request->has('remember');
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return redirect(route('login'))
            ->withErrors(['password' => 'The provided credentials do not match our records.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $msg = 'You have been logged out successfully.';
        session()->flash('success', $msg);

        return response()->json([
            'status' => true,
            'message' => $msg,
        ]);
    }
}
