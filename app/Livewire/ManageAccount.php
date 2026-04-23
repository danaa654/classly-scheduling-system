<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ManageAccount extends Component
{
    public $name, $email, $current_password, $new_password, $new_password_confirmation;
    
    // Toggle state
    public $showPassword = false; 

    public function togglePassword()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updatePassword()
{
    $this->validate([
        'current_password' => ['required', 'current_password'],
        'new_password' => [
            'required', 
            'confirmed', 
            Password::min(8)
                ->letters()
                ->mixedCase() // Requires at least one uppercase and one lowercase
                ->numbers()   // Requires at least one number
                ->symbols(),  // Requires at least one special character
        ],
    ]);

    auth()->user()->update([
        'password' => Hash::make($this->new_password)
    ]);

    $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
    $this->dispatch('toast', type: 'success', message: 'Security credentials updated successfully.');
}
    public function render()
    {
        return view('livewire.manage-account');
    }
}