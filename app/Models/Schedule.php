<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * These match the columns we added in the migration.
     */
    protected $fillable = [
        'subject_id',
        'room_id',
        'day',
        'time_slot',
    ];

    /**
     * Get the subject assigned to this schedule slot.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the room assigned to this schedule slot.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}