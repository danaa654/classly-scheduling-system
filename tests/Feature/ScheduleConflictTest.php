<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Room;
use App\Models\Faculty;
use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;  // ← ADD THIS
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;  // ← ADD THIS - Creates fresh DB for each test

    /**
     * Test 1: Detect overlapping schedules in the same room
     */
    public function test_detects_overlapping_schedules_in_same_room()
    {
        // Create test data using factories
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        // Create first schedule
        $existingSchedule = Schedule::factory()->create([
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:30'
        ]);

        // Test overlap
        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject,
            room: $room,
            day: 'Monday',
            startTime: '10:00',
            endTime: '11:00'
        );

        $this->assertFalse($result['success'], 'Overlapping schedules should be detected');
    }

    /**
     * Test 2: Allow adjacent schedules (touching but not overlapping)
     */
    public function test_allows_adjacent_schedules()
    {
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:30'
        ]);

        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject,
            room: $room,
            day: 'Monday',
            startTime: '10:30',
            endTime: '12:00'
        );

        $this->assertTrue($result['success'], 'Adjacent schedules should be allowed');
    }

    /**
     * Test 3: Prevent scheduling during lunch break
     */
    public function test_prevents_scheduling_during_lunch_break()
    {
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject,
            room: $room,
            day: 'Monday',
            startTime: '12:00',
            endTime: '13:00'
        );

        $this->assertFalse($result['success'], 'Should not allow scheduling during lunch break');
    }
}