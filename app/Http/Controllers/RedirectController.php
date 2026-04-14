<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke()
{
    $role = auth()->user()->role;

    if ($role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($role === 'registrar') {
        return redirect()->route('registrar.dashboard');
    } elseif ($role === 'dean') {
        return redirect()->route('dean.dashboard');
    } elseif ($role === 'ass.dean') { // MAKE SURE THIS MATCHES YOUR DB
        return redirect()->route('assistant-dean.dashboard');
    }

    abort(403, "Role mismatch: Found '$role'");
}
}