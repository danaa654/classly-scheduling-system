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


// Public Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// All protected system routes
Route::middleware(['auth'])->group(function () {

    // This is the landing page after login
    Route::get('/dashboard', RedirectController::class)->name('dashboard');

    // Dashboard Routes
    Route::get('/admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/registrar/dashboard', RegistrarDashboard::class)->name('registrar.dashboard');
    Route::get('/dean/dashboard', DeanDashboard::class)->name('dean.dashboard');
    Route::get('/assistant-dean/dashboard', AssistantDeanDashboard::class)->name('assistant-dean.dashboard');


    // Management & Core System
    Route::get('/manage-users', ManageUsers::class)->name('manage-users');
    Route::get('/faculty', App\Livewire\ManageFaculty::class)->name('faculty.index');
    Route::get('/faculty-load', FacultyLoading::class)->name('faculty-loading');
    Route::get('/subjects', ManageSubjects::class)->name('subjects');
    Route::get('/manage-rooms', ManageRooms::class)->name('manage-rooms');
    Route::get('/master-grid', MasterGrid::class)->name('master-grid');
    Route::get('/notifications', \App\Livewire\NotificationCenter::class)->name('notifications');
    Route::get('/settings', GlobalSettings::class)->name('settings');


});