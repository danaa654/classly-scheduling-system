<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_id', 
        'full_name', 
        'email', 
        'department', 
        'status',
        'employment_type',
        'teaching_specialization',
        'max_units',
        'requested_by',
        'rejection_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'max_units' => 'integer',
    ];

    /**
     * Get the user account associated with this faculty member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A faculty member can be assigned to many subjects (schedules).
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'faculty_id');
    }

    /**
     * Get the logs for this faculty member
     */
    public function logs()
    {
        return $this->hasMany(FacultyLog::class);
    }

    /**
     * Get the user who requested this faculty
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Scope to get all approved faculties
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get all pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get all rejected faculties
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
