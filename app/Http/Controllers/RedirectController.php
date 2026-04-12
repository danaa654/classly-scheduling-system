<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke()
{
    $user = auth()->user();
    
    // TEMPORARY DEBUG: This will stop the app and show you the role
    // dd($user->role); 

    $role = strtolower(trim($user->role));

    return match ($role) {
        'admin'     => redirect()->route('admin.dashboard'),
        'registrar' => redirect()->route('registrar.dashboard'),
        'dean'      => redirect()->route('dean.dashboard'),
        default     => abort(403, "Role mismatch: Found '{$role}'"),
    };
}
}