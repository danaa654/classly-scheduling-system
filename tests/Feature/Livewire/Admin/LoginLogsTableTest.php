<?php

use App\Livewire\Admin\LoginLogsTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // 1. Create test users with distinct academic roles
    $this->admin = User::factory()->create(['name' => 'Admin User', 'role' => 'admin']);
    $this->registrar = User::factory()->create(['name' => 'Rasmus Demo', 'role' => 'registrar']);
    $this->ccsStaff = User::factory()->create(['name' => 'CCS Staff Member', 'role' => 'associate_dean']);

    // 2. Insert mock login records into the login_logs table
    DB::table('login_logs')->insert([
        [
            'user_id' => $this->admin->id,
            'ip_address' => '192.168.1.1',
            'login_at' => now()->subHours(2),
            'logout_at' => now()->subHour(),
        ],
        [
            'user_id' => $this->registrar->id,
            'ip_address' => '192.168.1.2',
            'login_at' => now()->subHour(),
            'logout_at' => null, // Active Session
        ],
        [
            'user_id' => $this->ccsStaff->id,
            'ip_address' => '192.168.1.3',
            'login_at' => now(),
            'logout_at' => null,
        ]
    ]);
});

it('can render the system audit logs component successfully', function () {
    Livewire::test(LoginLogsTable::class)
        ->assertStatus(200)
        ->assertSee('System Audit Logs')
        ->assertSee('Rasmus Demo')
        ->assertSee('Active Session');
});

it('filters the audit logs correctly via the staff name search field', function () {
    Livewire::test(LoginLogsTable::class)
        ->set('search', 'Rasmus')
        ->assertSee('Rasmus Demo')
        ->assertDontSee('Admin User')
        ->assertDontSee('CCS Staff Member');
});

it('paginates the audit logs at exactly 10 records per page', function () {
    // Create 12 additional users and logs to trigger page 2 splits
    $extraUsers = User::factory()->count(12)->create(['role' => 'registrar']);
    
    foreach ($extraUsers as $index => $user) {
        DB::table('login_logs')->insert([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.' . (10 + $index),
            'login_at' => now(),
            'logout_at' => null,
        ]);
    }

    // Total records = 3 (from beforeEach) + 12 = 15 logs. Page 1 should only display 10.
    Livewire::test(LoginLogsTable::class)
        ->assertViewHas('logs', function ($logs) {
            return $logs->count() === 10 && $logs->total() === 15;
        });
});