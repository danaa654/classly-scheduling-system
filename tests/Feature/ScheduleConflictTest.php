<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Room;
use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Detect overlapping schedules in the same room ✅
     * 
     * This test verifies that when two schedules overlap in time,
     * the system detects a conflict.
     */
    public function test_detects_overlapping_schedules_in_same_room()
    {
        $room = Room::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        // Create first schedule: 9:00 - 10:30
        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00'
        ]);

        // Test overlap: 10:00 - 11:00 (overlaps with 9:00-10:30)
        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject2,
            room: $room,
            day: 'Monday',
            startTime: '10:00:00',
            endTime: '11:00:00'
        );

        $this->assertFalse($result['success'], 'Overlapping schedules should be detected');
    }

    /**
     * Test 2: Different room, no conflict
     * 
     * When schedules are in DIFFERENT rooms, there should be no conflict
     * even if they overlap in time. Use DIFFERENT sections to avoid section conflict.
     */
    public function test_allows_same_time_different_rooms()
    {
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();
        
        // Create subjects with DIFFERENT sections to avoid section conflicts
        $subject1 = Subject::factory()->create(['section' => 'A', 'major' => 'Systems']);
        $subject2 = Subject::factory()->create(['section' => 'B', 'major' => 'Networks']);

        // Create first schedule in Room 1: 9:00 - 10:30
        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room1->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00'
        ]);

        // Try same time in DIFFERENT room (Room 2)
        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject2,
            room: $room2,  // ← Different room!
            day: 'Monday',
            startTime: '09:00:00',
            endTime: '10:30:00'
        );

        // Should succeed - different rooms can have same time
        $this->assertTrue(
            $result['success'], 
            'Same time in different rooms should be allowed. Got: ' . json_encode($result)
        );
    }

    /**
     * Test 3: Different day, same room, no conflict
     * 
     * When schedules are on DIFFERENT days in the SAME room,
     * there should be no conflict
     */
    public function test_allows_same_room_different_days()
    {
        $room = Room::factory()->create();
        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        // Create first schedule on Monday: 9:00 - 10:30
        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00'
        ]);

        // Try same time on DIFFERENT day (Tuesday)
        $service = app(ScheduleConflictService::class);
        $result = $service->validatePlacement(
            subject: $subject2,
            room: $room,
            day: 'Tuesday',  // ← Different day!
            startTime: '09:00:00',
            endTime: '10:30:00'
        );

        // Should succeed - different days can use same room and time
        $this->assertTrue($result['success'], 'Same room/time on different days should be allowed');
    }
}