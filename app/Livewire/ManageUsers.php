<?php
namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class ManageUsers extends Component
{
    public $name, $email, $password, $role = 'registrar', $department = 'All Department';
    public $editingUserId, $showModal = false;

    // Your specific Institutional options
    public $roles = ['admin', 'registrar', 'dean', 'oic', 'ass. dean'];
    public $departments = [
        'All Department', 
        'College of Computer Studies', 
        'College of Teacher Education', 
        'College of Criminology', 
        'School of Hospitality and Tourism Management'
    ];

    public function mount()
    {
    // Block anyone who isn't an 'admin' from seeing this page
    if (auth()->user()->role !== 'admin') {
        abort(403, 'Only Administrators can manage user accounts.');
    }
    }

    public function openModal() {
        $this->reset(['name', 'email', 'password', 'role', 'department', 'editingUserId']);
        $this->showModal = true;
    }

    public function saveUser() {
        $data = $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => 'required',
            'department' => 'required',
        ]);

        if ($this->editingUserId) {
            $user = User::find($this->editingUserId);
            $user->update($data);
            if ($this->password) { $user->update(['password' => Hash::make($this->password)]); }
        } else {
            $this->validate(['password' => 'required|min:8']);
            User::create(array_merge($data, ['password' => Hash::make($this->password)]));
        }

        $this->showModal = false;
        session()->flash('message', 'User list updated successfully.');
    }

    public function editUser($id) {
        $user = User::findOrFail($id);
        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->department = $user->department;
        $this->showModal = true;
    }

    public function deleteUser($id) {
        User::destroy($id);
        session()->flash('message', 'User removed.');
    }

    public function render() {
        return view('livewire.manage-users', ['users' => User::latest()->get()]);
    }
}