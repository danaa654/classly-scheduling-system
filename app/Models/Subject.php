<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    // Essential for Bulk Import to work
    protected $fillable = [
    'edp_code', 
    'subject_code', 
    'description', 
    'units', 
    'department',
    'type',
    'duration_hours'
    
];
}