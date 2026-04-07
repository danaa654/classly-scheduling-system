<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\AdminDashboard;
use App\Livewire\RegistrarDashboard;
use App\Livewire\DeanDashboard; // New component for Deans/OICs
use App\Livewire\MasterGrid;
use App\Livewire\ManageUsers;
use App\Livewire\ManageRooms;
use App\Livewire\ManageSubjects;
use App\Livewire\ManageFaculty;
use App\Livewire\FacultyLoading; // New component for Departmental work

// Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * SMART REDIRECTOR
     * Automatically sends users to their specific dashboard based on 'role' column.
     */
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        if ($role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($role === 'registrar') {
            return redirect()->route('registrar.dashboard');
        }

        // Redirect Deans and OICs to the Departmental Dashboard
        if (in_array($role, ['dean', 'oic'])) {
            return redirect()->route('dean.dashboard');
        }

        // Default fallback if role is missing or unauthorized
        abort(403, 'Unauthorized access: Your role (' . $role . ') does not have a dashboard.');
    })->name('dashboard');

    /**
     * ADMIN & REGISTRAR ROUTES
     * High-level institutional management
     */
    Route::get('/admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/registrar/dashboard', RegistrarDashboard::class)->name('registrar.dashboard');
    Route::get('/dean/dashboard', DeanDashboard::class)->name('dean.dashboard');
    Route::get('/manage-users', ManageUsers::class)->name('manage-users');
    

    /**
     * DEAN & OIC ROUTES
     * Specific to academic department management
     */
    Route::get('/faculty-load', FacultyLoading::class)->name('faculty-loading');

    /**
     * SHARED ACCESSIBLE ROUTES
     * These components use internal logic to filter data by 'department'.
     */
    Route::get('/manage-rooms', ManageRooms::class)->name('manage-rooms');
    Route::get('/master-grid', MasterGrid::class)->name('master-grid');
    Route::get('/subjects', ManageSubjects::class)->name('subjects');
    Route::get('/faculty', ManageFaculty::class)->name('manage-faculty');
    
    

});

require __DIR__.'/settings.php';