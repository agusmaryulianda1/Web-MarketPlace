<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'vendor' => redirect()->route('vendor.dashboard'),
            'buyer' => $this->buyerRedirect($request),
            default => abort(403),
        };
    }

    private function buyerRedirect(Request $request): RedirectResponse
    {
        $intended = $request->session()->get('url.intended');
        $parts = is_string($intended) ? parse_url($intended) : false;
        $path = $parts['path'] ?? '';
        $host = $parts['host'] ?? null;

        if (is_array($parts) && str_starts_with($path, '/buyer/') && ($host === null || $host === $request->getHost())) {
            return redirect()->to($intended);
        }

        return redirect()->route('buyer.dashboard');
    }
}