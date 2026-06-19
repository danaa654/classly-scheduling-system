<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class LoginLogsTable extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Fetch logs cleanly, joining the users table to display their actual names/roles
        $logs = DB::table('login_logs')
            ->join('users', 'login_logs.user_id', '=', 'users.id')
            ->select('login_logs.*', 'users.name as user_name', 'users.role as user_role')
            ->where('users.name', 'like', '%' . $this->search . '%')
            ->orderBy('login_logs.login_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.login-logs-table', [
            'logs' => $logs
        ]);
    }
}