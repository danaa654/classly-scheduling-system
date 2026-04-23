<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke()
{
    $role = auth()->user()->role;

    return match ($role) {
        'admin'          => redirect()->route('admin.dashboard'),
        'registrar'      => redirect()->route('registrar.dashboard'),
        'dean', 'oic'    => redirect()->route('dean.dashboard'),
        'associate_dean' => redirect()->route('assistant-dean.dashboard'),
        default          => abort(403, "Role '{$role}' is not configured for a dashboard."),
    };
}
}