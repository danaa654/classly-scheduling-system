<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class ManageUsers extends Component
{
    public $name, $email, $password, $role = 'registrar', $department = 'All Department';
    public $editingUserId, $showModal = false;

    // Standardized Roles (No dots to avoid middleware issues)
    public $roles = ['admin', 'registrar', 'dean', 'oic', 'associate_dean'];

    // Standardized Department Acronyms
    public $departments = [
        'All Department', 
        'CCS', 
        'CTE', 
        'COC', 
        'SHTM'
    ];

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only Administrators can manage user accounts.');
        }
    }

    public function openModal() {
        $this->reset(['name', 'email', 'password', 'role', 'department', 'editingUserId']);
        $this->showModal = true;
    }

    public function saveUser()
{
    $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users,email,' . ($this->editingUserId ?? 'NULL'),
        'role' => 'required',
        'department' => 'required',
    ];

    // Only require password if creating a new user
    if (!$this->editingUserId) {
        $rules['password'] = 'required|min:8';
    }

    $this->validate($rules);

    if ($this->editingUserId) {
        // UPDATE LOGIC
        $user = User::find($this->editingUserId);
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'department' => $this->department,
            // Notice: Password is NOT updated here
        ]);
        session()->flash('message', 'User profile updated successfully.');
    } else {
        // CREATE LOGIC
        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'department' => $this->department,
        ]);
        session()->flash('message', 'Account created successfully.');
    }

    $this->showModal = false;
    $this->reset(['name', 'email', 'password', 'role', 'department', 'editingUserId']);
}

    public function editUser($id) {
        $user = User::findOrFail($id);
        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        // Logic: Convert NULL back to 'All Department' for the dropdown
        $this->department = $user->department ?? 'All Department';
        $this->showModal = true;
    }

    public function deleteUser($id) {
        if ($id !== auth()->id()) { // Prevent admin from deleting themselves
            User::destroy($id);
            session()->flash('message', 'User removed.');
        }
    }

    public function render() {
        return view('livewire.manage-users', ['users' => User::latest()->get()]);
    }
}