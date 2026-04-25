<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // Add this to link to the Users table
        'employee_id', 
        'full_name', 
        'email', 
        'department', 
        'status',
        'requested_by',
        'rejection_reason',
    ];

    /**
     * Get the user account associated with this faculty member.
     */
    public function user()
    {
        // This is the missing link that was causing your error!
        return $this->belongsTo(User::class);
    }

    /**
     * A faculty member can be assigned to many subjects (schedules).
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class); 
    }
}