<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * * This allows the bulk import and form saving to work
     * by explicitly naming the columns we want to fill.
     */
    protected $fillable = [
        'subject_code',
        'description',
        'units',
        'department',
    ];
}