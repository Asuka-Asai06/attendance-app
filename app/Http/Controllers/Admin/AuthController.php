<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * 管理者ログイン画面を表示
     */
    public function create(): View
    {
        return view('admin.admin-login');
    }

    /**
     * 管理者としてログイン
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'admin_status' => true,
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'ログイン情報が登録されていません',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }
}
