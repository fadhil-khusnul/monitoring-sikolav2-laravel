<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cookie;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $baseUrl = env('OAUTH_API_NEOSIA');




        $response = Http::post($baseUrl, [
            'grant_type' => 'password',
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'username' => env('OAUTH_NEOSIA_USERNAME'),
            'password' => env('OAUTH_NEOSIA_PASSWORD'),
            'scope' => '*',
        ]);

        $response = $response->json();
        $token = $response['access_token'];
        Cookie::queue('access_token', $token, 60 * 24); // 1 day


        // if (Auth::user()->role->name === 'universitas') {
        //     return redirect()->intended(route('universitas.dashboard', absolute: false));
        // }
        // if (Auth::user()->role->name === 'fakultas') {
        //     return redirect()->intended(route('fakultas.dashboard', absolute: false));
        // }
        // if (Auth::user()->role->name === 'prodi') {
        //     return redirect()->intended(route('prodi.dashboard', absolute: false));
        // }


        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
