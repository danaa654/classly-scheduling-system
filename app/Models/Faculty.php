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
        'employee_id', 
        'full_name', 
        'email', 
        'department', 
        'status',
        'requested_by',
        'rejection_reason',
    ];

    public function subjects()
{
    // A faculty member can be assigned to many subjects (schedules)
    return $this->hasMany(Subject::class); 
}
}
