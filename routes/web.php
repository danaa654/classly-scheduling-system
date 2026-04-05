<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\AdminDashboard;
use App\Livewire\RegistrarDashboard;
use App\Livewire\ScheduleBoard;
use App\Livewire\ManageUsers;
use App\Livewire\ManageRooms;
use App\Livewire\ManageSubjects;
use App\Livewire\ManageFaculty;
use Illuminate\Support\Facades\Auth;

// Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * SMART REDIRECTOR
     * This fixes the 403 error by routing users based on their role 
     * as soon as they hit the /dashboard URL.
     */
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        if ($role === 'registrar') {
            return redirect()->route('registrar.dashboard');
        }

        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Default fallback if role is missing
        abort(403, 'Unauthorized access: Role not defined.');
    })->name('dashboard');

    /**
     * ADMIN SPECIFIC ROUTES
     */
    Route::get('/admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/manage-users', ManageUsers::class)->name('manage-users');

    /**
     * REGISTRAR SPECIFIC ROUTES
     */
    Route::get('/registrar/dashboard', RegistrarDashboard::class)->name('registrar.dashboard');

    /**
     * SHARED ACCESSIBLE ROUTES
     */
    Route::get('/manage-rooms', ManageRooms::class)->name('manage-rooms');
    Route::get('/scheduler', ScheduleBoard::class)->name('scheduler');
    Route::get('/subjects', ManageSubjects::class)->name('subjects');
    Route::get('/faculty', ManageFaculty::class)->name('manage-faculty');


});

require __DIR__.'/settings.php';