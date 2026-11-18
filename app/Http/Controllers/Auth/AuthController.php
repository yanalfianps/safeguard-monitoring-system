<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login page.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->isAdminInventory()) {
                return redirect()->route('inventory.index', ['tab' => 'borrowed']);
            }
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $emailNormalized = Str::lower(trim($validated['email']));
        $throttleKey = Str::transliterate($emailNormalized . '|' . $request->ip());
        $maxAttempts = 10;
        $decaySeconds = 300; // 5 menit

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$minutes} menit ({$seconds} detik).",
            ])->onlyInput('email');
        }

        $credentials = [
            'email' => trim($validated['email']),
            'password' => $validated['password'],
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user status is inactive
            if (!$user->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::hit($throttleKey, $decaySeconds);

                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);

            // Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            // Audit Activity Log
            try {
                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'module' => 'Autentikasi',
                    'action' => 'LOGIN',
                    'record_type' => get_class($user),
                    'record_id' => $user->id,
                    'description' => "Pengguna {$user->name} ({$user->email}) berhasil login ke sistem.",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore log error to not block core login flow
            }

            if ($user->isAdminInventory()) {
                return redirect()->route('inventory.index', ['tab' => 'borrowed'])
                    ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        RateLimiter::hit($throttleKey, $decaySeconds);

        return back()->withErrors([
            'email' => 'Kombinasi email dan password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            try {
                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'module' => 'Autentikasi',
                    'action' => 'LOGOUT',
                    'record_type' => get_class($user),
                    'record_id' => $user->id,
                    'description' => "Pengguna {$user->name} ({$user->email}) keluar dari sistem (logout).",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}

