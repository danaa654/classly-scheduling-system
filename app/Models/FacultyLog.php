<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyLog extends Model
{
    use HasFactory;

    /**
     * Mass Assignment Protection
     * This solves the SQL error: "Field 'faculty_id' doesn't have a default value"
     * by allowing Laravel to pass these values to the database.
     */
    protected $fillable = [
        'faculty_id',
        'user_id',
        'action',
        'description',
    ];

    /**
     * Relationship to Faculty
     * Allows you to access the faculty member associated with the log.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Relationship to User (The Actor)
     * This solves the "RelationNotFoundException" when calling ->with('user')
     * in your Livewire render method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}