<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->string('phone')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => $request->string('role')->toString(),
            ]);

            if ($user->role === 'vendor') {
                $user->vendor()->create(['status' => 'active']);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return match ($user->role) {
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