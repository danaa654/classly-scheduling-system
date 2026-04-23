<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedirectController;
use App\Livewire\AdminDashboard;
use App\Livewire\RegistrarDashboard;
use App\Livewire\DeanDashboard;
use App\Livewire\MasterGrid;
use App\Livewire\ManageUsers;
use App\Livewire\ManageRooms;
use App\Livewire\ManageSubjects;
use App\Livewire\ManageFaculty;
use App\Livewire\FacultyLoading;
use App\Livewire\AssistantDeanDashboard;
use App\Livewire\NotificationCenter;
use App\Livewire\GlobalSettings;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// All protected system routes
Route::middleware(['auth'])->group(function () {

    // The "Traffic Controller": This route sends users to their specific roles
    Route::get('/dashboard', RedirectController::class)->name('dashboard');

    // 1. Admin & Registrar (No extra middleware needed if they are global)
    Route::get('/admin/dashboard', AdminDashboard::class)
        ->name('admin.dashboard')
        ->middleware('role:admin');

    Route::get('/registrar/dashboard', RegistrarDashboard::class)
        ->name('registrar.dashboard')
        ->middleware('role:registrar');

    // 2. Dean & OIC (Merged into the same dashboard)
    // Removed the nested 'auth' here because the parent group already has it
    Route::middleware(['role:dean,oic'])->group(function () {
        Route::get('/dean/dashboard', DeanDashboard::class)->name('dean.dashboard');
    });

    // 3. Associate Dean (Using the exact new slug)
    Route::middleware(['role:associate_dean'])->group(function () {
        Route::get('/assistant-dean/dashboard', AssistantDeanDashboard::class)->name('assistant-dean.dashboard');
    });

    // Management & Core System
    Route::get('/manage-users', ManageUsers::class)->name('manage-users');
    Route::get('/faculty', App\Livewire\ManageFaculty::class)->name('faculty.index');
    Route::get('/faculty-load', FacultyLoading::class)->name('faculty-loading');
    Route::get('/subjects', ManageSubjects::class)->name('subjects');
     Route::get('/manage-rooms', \App\Livewire\ManageRooms::class)->name('manage.rooms');
    Route::get('/master-grid', MasterGrid::class)->name('master-grid');
    Route::get('/notifications', \App\Livewire\NotificationCenter::class)->name('notifications');
    Route::get('/manage-account', \App\Livewire\ManageAccount::class)->name('manage-account');
    Route::get('/settings', GlobalSettings::class)->name('settings');


});