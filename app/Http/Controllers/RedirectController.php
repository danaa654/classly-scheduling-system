<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return match ($user->role) {

            'admin' => redirect()->route('admin.dashboard'),

            'registrar' => redirect()->route('registrar.dashboard'),

            'dean',
            'oic' => redirect()->route('dean.dashboard'),

            'associate_dean' => redirect()->route('assistant-dean.dashboard'),

            default => abort(403),
        };
    }
}