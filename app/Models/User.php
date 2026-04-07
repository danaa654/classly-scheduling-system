<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Enhanced User model to support Role-Based Access Control (RBAC).
 * 'role' tracks user permissions (registrar, dean, oic).
 * 'department' restricts Deans/OICs to specific academic data.
 */
#[Fillable([
    'name', 
    'email', 
    'password', 
    'current_team_id', 
    'role', 
    'department'
])]
#[Hidden([
    'password', 
    'two_factor_secret', 
    'two_factor_recovery_codes', 
    'remember_token'
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Helper to check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Helper to check if the user is an administrative official (Dean or OIC).
     */
    public function isDepartmentOfficial(): bool
    {
        return in_array($this->role, ['dean', 'oic']);
    }

    /**
     * Get the user's initials for the profile avatar.
     * FIXED: Moves ->upper() after the join/implode to avoid Collection error.
     */
   // Find the initials() function in app/Models/User.php
// In app/Models/User.php
public function initials()
{
    $initials = Str::of($this->name)
        ->explode(' ')
        ->take(2)
        ->map(fn ($word) => Str::substr($word, 0, 1))
        ->implode('');

    return Str::upper($initials); // Use the Facade to handle the plain string
}
}