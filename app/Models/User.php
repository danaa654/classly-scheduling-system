<?php

namespace App\Models;

use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
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
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_team_id',
        'role',
        'department',
        'can_finalize_schedule',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'can_finalize_schedule'   => 'boolean',
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
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->is_admin === true;
    }

    /**
     * Helper to check if the user is an administrative official (Dean or OIC).
     */
    public function isDepartmentOfficial(): bool
    {
        return in_array($this->role, ['dean', 'oic']);
    }

    /**
     * Whether this user (registrar) may finalize schedules.
     * Admin always can; Registrar only when explicitly delegated.
     */
    public function canFinalizeSchedule(): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role === 'registrar') {
            return (bool) ($this->can_finalize_schedule ?? false);
        }

        return false;
    }

    /**
     * Permission log entries performed by this user.
     */
    public function permissionLogsPerformed()
    {
        return $this->hasMany(PermissionLog::class, 'performed_by');
    }

    /**
     * Permission log entries targeting this user.
     */
    public function permissionLogsReceived()
    {
        return $this->hasMany(PermissionLog::class, 'target_user_id');
    }

    /**
     * Get the user's initials for the profile avatar.
     */
    public function initials(): string
    {
        $initials = Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');

        return Str::upper($initials);
    }
}