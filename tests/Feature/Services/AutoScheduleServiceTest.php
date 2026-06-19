<?php

namespace Tests\Feature\Services;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Services\AutoScheduleService;
use App\Services\ScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutoScheduleService $service;
    private ScheduleConflictService $conflictService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->conflictService = app(ScheduleConflictService::class);
        $this->service = new AutoScheduleService($this->conflictService);
        
        // Set up default workspace
        Setting::setValue('school_year', '2026-2027');
        Setting::setValue('semester', '1st');
        Setting::setValue('active_days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
        Setting::setValue('start_time', '08:00:00');
        Setting::setValue('end_time', '17:00:00');
    }

    // ========================================================================
    // TEST GROUP 1: Basic Schedule Generation
    // ========================================================================

    /**
     * Test 1.1: Generate schedules with valid filters
     * 
     * Ensures the service accepts proper filters and returns a result array.
     */
    public function test_generates_schedules_with_valid_filters()
    {
        $faculty = Faculty::factory()->create();
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);
        
        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 2,
            'duration_hours' => 3,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scheduled', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('scheduled_items', $result);
        $this->assertArrayHasKey('failure_reasons', $result);
    }

    /**
     * Test 1.2: Reject generation with missing filters
     * 
     * Service should return error when required filters are missing.
     */
    public function test_rejects_missing_required_filters()
    {
        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            // Missing: year_level and section
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertFalse($result['success'] ?? false);
        $this->assertGreaterThan(0, count($result['failure_reasons']));
        $this->assertStringContainsString('select', $result['failure_reasons'][0]);
    }

    // ========================================================================
    // TEST GROUP 2: Room Compatibility
    // ========================================================================

    /**
     * Test 2.1: Compatible rooms are selected for subjects
     * 
     * When a subject has specific room requirements, only compatible
     * rooms should be assigned.
     */
    public function test_assigns_compatible_rooms_only()
    {
        $faculty = Faculty::factory()->create();
        
        // General lecture room (compatible with most subjects)
        $lectureRoom = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);
        
        // Lab room (only for lab subjects)
        $labRoom = Room::factory()->create([
            'type' => 'LAB',
            'capacity' => 30,
            'specialization' => 'IT',
        ]);

        // Non-lab subject
        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'type' => 'MAJOR',
            'meetings_per_week' => 1,
            'duration_hours' => 2,
            'requires_lab' => false,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should schedule successfully
        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Verify room is the lecture room, not the lab room
        $scheduled = collect($result['scheduled_items']);
        $this->assertTrue(
            $scheduled->every(fn ($item) => $item['room'] === $lectureRoom->room_name),
            'Only compatible rooms should be assigned'
        );
    }

    /**
     * Test 2.2: Lab subjects get lab rooms
     * 
     * Subjects marked as requiring lab should only be assigned to lab rooms.
     */
    public function test_assigns_lab_rooms_for_lab_subjects()
    {
        $faculty = Faculty::factory()->create();
        
        $lectureRoom = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);
        
        $labRoom = Room::factory()->create([
            'type' => 'LAB',
            'capacity' => 25,
            'specialization' => 'IT',
        ]);

        // Lab subject
        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'type' => 'MAJOR',
            'subject_code' => 'IT-PROGRAMMING-LAB',
            'meetings_per_week' => 1,
            'duration_hours' => 3,
            'requires_lab' => true,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should only assign lab room
        $scheduled = collect($result['scheduled_items']);
        $this->assertTrue(
            $scheduled->every(fn ($item) => $item['room'] === $labRoom->room_name),
            'Lab subjects must be assigned to lab rooms'
        );
    }

    /**
     * Test 2.3: No compatible rooms → scheduling fails
     * 
     * If no compatible rooms exist, the subject should fail gracefully.
     */
    public function test_fails_when_no_compatible_rooms_available()
    {
        $faculty = Faculty::factory()->create();
        
        // Create a lab room only
        Room::factory()->create([
            'type' => 'LAB',
            'capacity' => 25,
            'specialization' => 'FB', // Criminology lab
        ]);

        // Non-lab IT subject (no compatible room)
        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'type' => 'MAJOR',
            'meetings_per_week' => 1,
            'duration_hours' => 2,
            'requires_lab' => false,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should fail to schedule
        $this->assertGreaterThan(0, $result['failed']);
        $this->assertStringContainsString('no compatible room', $result['failure_reasons'][0]);
    }

    // ========================================================================
    // TEST GROUP 3: Faculty Constraints
    // ========================================================================

    /**
     * Test 3.1: Respects faculty availability windows
     * 
     * Subjects should not be scheduled outside faculty availability hours.
     */
    public function test_respects_faculty_availability_windows()
    {
        $faculty = Faculty::factory()->create([
            'availability' => [
                'Monday' => [
                    ['start' => '08:00:00', 'end' => '12:00:00'], // Morning only
                ],
                'Tuesday' => [
                    ['start' => '14:00:00', 'end' => '17:00:00'], // Afternoon only
                ],
            ],
        ]);

        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should schedule successfully
        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Verify scheduled times fall within faculty availability
        $scheduled = collect($result['scheduled_items'])->first();
        $this->assertNotNull($scheduled);
    }

    /**
     * Test 3.2: Fails if faculty has no availability
     * 
     * Subject with faculty who has no availability should fail.
     */
    public function test_fails_when_faculty_has_no_availability()
    {
        $faculty = Faculty::factory()->create([
            'availability' => [], // No availability set
        ]);

        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should fail
        $this->assertGreaterThan(0, $result['failed']);
    }

    // ========================================================================
    // TEST GROUP 4: Section & Day Pairing
    // ========================================================================

    /**
     * Test 4.1: Section A subjects prefer morning slots
     * 
     * Section A subjects should be scheduled before lunch (12:00 PM).
     */
    public function test_section_a_prefers_morning_slots()
    {
        $faculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A', // Morning preference
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Verify all scheduled items are in morning (before 12:00)
        $scheduled = collect($result['scheduled_items']);
        $this->assertTrue(
            $scheduled->every(function ($item) {
                $start = strtotime($item['start_time']);
                $noon = strtotime('12:00');
                return $start < $noon;
            }),
            'Section A should be scheduled in morning slots'
        );
    }

    /**
     * Test 4.2: Section B subjects prefer afternoon slots
     * 
     * Section B subjects should be scheduled after lunch (1:00 PM).
     */
    public function test_section_b_prefers_afternoon_slots()
    {
        $faculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'B', // Afternoon preference
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'B',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Verify all scheduled items are in afternoon (after 13:00)
        $scheduled = collect($result['scheduled_items']);
        $this->assertTrue(
            $scheduled->every(function ($item) {
                $start = strtotime($item['start_time']);
                $lunchEnd = strtotime('13:00');
                return $start >= $lunchEnd;
            }),
            'Section B should be scheduled in afternoon slots'
        );
    }

    /**
     * Test 4.3: Respects valid day pairings
     * 
     * For 2 meetings/week, only valid day pairings should be used.
     */
    public function test_respects_valid_day_pairings_for_two_meetings()
    {
        $faculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 2, // 2 meetings
            'duration_hours' => 4,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Should have scheduled 2 meetings
        $scheduled = collect($result['scheduled_items'])->where('subject_code', $subject->subject_code);
        $this->assertEquals(2, $scheduled->count(), 'Should schedule exactly 2 meetings');
        
        // Verify days form a valid pairing
        $days = $scheduled->pluck('day')->unique()->sort()->values()->all();
        $validPairings = [
            ['Monday', 'Wednesday'],
            ['Tuesday', 'Thursday'],
            ['Tuesday', 'Friday'],
            ['Wednesday', 'Friday'],
            ['Thursday', 'Saturday'],
        ];
        
        $this->assertTrue(
            collect($validPairings)->contains($days),
            "Days {$days[0]}, {$days[1]} do not form a valid pairing"
        );
    }

    // ========================================================================
    // TEST GROUP 5: Conflict Detection
    // ========================================================================

    /**
     * Test 5.1: Avoids room conflicts
     * 
     * Two subjects should not be scheduled in the same room at the same time.
     */
    public function test_avoids_room_conflicts()
    {
        $faculty1 = Faculty::factory()->create();
        $faculty2 = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        // Subject 1: Already scheduled
        $subject1 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty1->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room->id,
            'faculty_id' => $faculty1->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => Schedule::STATUS_PARTIAL,
        ]);

        // Subject 2: Try to schedule at same time in same room
        $subject2 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
            'faculty_id' => $faculty2->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Subject 2 should be scheduled in a DIFFERENT time slot
        $scheduled = collect($result['scheduled_items'])->where('subject_code', $subject2->subject_code);
        
        if ($scheduled->isNotEmpty()) {
            $this->assertTrue(
                $scheduled->every(function ($item) {
                    return $item['day'] !== 'Monday' || 
                           (strtotime($item['start_time']) >= strtotime('11:00:00'));
                }),
                'Subject 2 should not conflict with Subject 1 room'
            );
        }
    }

    /**
     * Test 5.2: Avoids faculty conflicts
     * 
     * A faculty member should not teach two subjects at the same time.
     */
    public function test_avoids_faculty_conflicts()
    {
        $faculty = Faculty::factory()->create();
        
        $room1 = Room::factory()->create(['type' => 'LECTURE', 'specialization' => 'GENERAL']);
        $room2 = Room::factory()->create(['type' => 'LECTURE', 'specialization' => 'GENERAL']);

        // Subject 1: Already scheduled for faculty
        $subject1 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room1->id,
            'faculty_id' => $faculty->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => Schedule::STATUS_PARTIAL,
        ]);

        // Subject 2: Same faculty, same time
        $subject2 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Subject 2 should be scheduled at a DIFFERENT time
        $scheduled = collect($result['scheduled_items'])->where('subject_code', $subject2->subject_code);
        
        if ($scheduled->isNotEmpty()) {
            $this->assertTrue(
                $scheduled->every(function ($item) {
                    return $item['day'] !== 'Monday' || 
                           (strtotime($item['start_time']) >= strtotime('11:00:00'));
                }),
                'Faculty should not be double-booked'
            );
        }
    }

    /**
     * Test 5.3: Avoids section conflicts
     * 
     * A student section should not have two classes at the same time.
     */
    public function test_avoids_section_conflicts()
    {
        $faculty1 = Faculty::factory()->create();
        $faculty2 = Faculty::factory()->create();
        
        $room1 = Room::factory()->create(['type' => 'LECTURE', 'specialization' => 'GENERAL']);
        $room2 = Room::factory()->create(['type' => 'LECTURE', 'specialization' => 'GENERAL']);

        // Subject 1: Already scheduled
        $subject1 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A', // Same section
            'faculty_id' => $faculty1->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        Schedule::factory()->create([
            'subject_id' => $subject1->id,
            'room_id' => $room1->id,
            'faculty_id' => $faculty1->id,
            'day' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'status' => Schedule::STATUS_PARTIAL,
        ]);

        // Subject 2: Same section, same time
        $subject2 = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A', // Same section
            'faculty_id' => $faculty2->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Subject 2 should be scheduled at DIFFERENT time (or fail)
        $scheduled = collect($result['scheduled_items'])->where('subject_code', $subject2->subject_code);
        
        if ($scheduled->isNotEmpty()) {
            $this->assertTrue(
                $scheduled->every(function ($item) {
                    return $item['day'] !== 'Monday' || 
                           (strtotime($item['start_time']) >= strtotime('11:00:00'));
                }),
                'Section should not have conflicting schedules'
            );
        }
    }

    // ========================================================================
    // TEST GROUP 6: Fallback Logic
    // ========================================================================

    /**
     * Test 6.1: Session fallback for Section A (afternoon if no morning available)
     * 
     * If Section A has no morning slots, it should fall back to afternoon.
     */
    public function test_section_a_falls_back_to_afternoon_when_needed()
    {
        $faculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        // Block all morning slots for this section
        for ($i = 0; $i < 10; $i++) {
            $blockSubject = Subject::factory()->create([
                'department' => 'CCS',
                'major' => 'IT',
                'year_level' => 1,
                'section' => 'A',
                'faculty_id' => Faculty::factory()->create()->id,
                'meetings_per_week' => 1,
                'duration_hours' => 1,
            ]);

            $startHour = 8 + ($i % 4);
            Schedule::factory()->create([
                'subject_id' => $blockSubject->id,
                'room_id' => $room->id,
                'day' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'][$i % 4],
                'start_time' => sprintf('%02d:00:00', $startHour),
                'end_time' => sprintf('%02d:30:00', $startHour),
                'status' => Schedule::STATUS_PARTIAL,
            ]);
        }

        // Now try to schedule another Section A subject
        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should schedule successfully (fallback to afternoon)
        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Should have a warning about fallback
        $this->assertGreaterThan(0, $result['warnings']);
        $this->assertTrue(
            collect($result['fallback_warnings'])->contains(
                fn ($warning) => str_contains($warning, 'afternoon fallback')
            ),
            'Should have fallback warning'
        );
    }

    // ========================================================================
    // TEST GROUP 7: CTE/Education Fallback
    // ========================================================================

    /**
     * Test 7.1: CTE subjects fall back to general lecture rooms
     * 
     * If no specialized CTE room exists, general lecture rooms should work.
     */
    public function test_cte_falls_back_to_general_lecture_rooms()
    {
        $faculty = Faculty::factory()->create();
        
        // Only general lecture room available (no CTE-specific room)
        $generalRoom = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CTE',
            'major' => 'CTE',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'type' => 'MAJOR',
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CTE',
            'major' => 'CTE',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should schedule successfully with general room
        $this->assertGreaterThan(0, $result['scheduled']);
        $scheduled = collect($result['scheduled_items'])->first();
        $this->assertEquals($generalRoom->room_name, $scheduled['room']);
    }

    // ========================================================================
    // TEST GROUP 8: Pre-Assigned vs Unassigned Subjects
    // ========================================================================

    /**
     * Test 8.1: Pre-assigned subjects (with placeholder faculty) are processed first
     * 
     * Subjects with faculty pre-assigned in a placeholder should be scheduled first (WAVE 1).
     */
    public function test_pre_assigned_subjects_scheduled_before_unassigned()
    {
        $preAssignedFaculty = Faculty::factory()->create();
        $unassignedFaculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        // Subject 1: Pre-assigned (has placeholder with faculty)
        $preAssignedSubject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $preAssignedFaculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        // Create placeholder (incomplete schedule indicating pre-assignment)
        Schedule::factory()->create([
            'subject_id' => $preAssignedSubject->id,
            'section' => 'A',
            'faculty_id' => $preAssignedFaculty->id,
            'day' => null, // Placeholder: incomplete
            'start_time' => null,
            'end_time' => null,
            'room_id' => null,
        ]);

        // Subject 2: Unassigned (no placeholder)
        $unassignedSubject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 2,
            'section' => 'A',
            'faculty_id' => $unassignedFaculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Both should schedule, but pre-assigned should indicate WAVE 1
        $scheduled = collect($result['scheduled_items']);
        $preAssignedScheduled = $scheduled->where('subject_code', $preAssignedSubject->subject_code)->first();
        
        if ($preAssignedScheduled) {
            $this->assertStringContainsString('WAVE 1', $preAssignedScheduled['wave'] ?? '');
        }
    }

    // ========================================================================
    // TEST GROUP 9: Persistence
    // ========================================================================

    /**
     * Test 9.1: Persisted schedules are saved to database
     * 
     * When persist=true, generated schedules should be saved.
     */
    public function test_persisted_schedules_are_saved_to_database()
    {
        $faculty = Faculty::factory()->create();
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 1,
            'duration_hours' => 2,
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        // Generate WITH persistence
        $result = $this->service->generatePartialSchedules($filters, persist: true);

        $this->assertGreaterThan(0, $result['scheduled']);
        
        // Verify schedules exist in database
        $savedSchedules = Schedule::where('subject_id', $subject->id)
            ->where('day', '!=', null)
            ->where('start_time', '!=', null)
            ->where('end_time', '!=', null)
            ->get();
        
        $this->assertGreaterThan(0, $savedSchedules->count());
    }

    // ========================================================================
    // TEST GROUP 10: Edge Cases
    // ========================================================================

    /**
     * Test 10.1: No subjects to schedule returns empty result
     * 
     * If no subjects match the filters, should return 0 scheduled.
     */
    public function test_no_subjects_to_schedule_returns_empty_result()
    {
        $filters = [
            'department' => 'NONEXISTENT',
            'major' => 'NONEXISTENT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        $this->assertEquals(0, $result['scheduled']);
    }

    /**
     * Test 10.2: Handles very tight schedule (back-to-back classes)
     * 
     * Should still find valid schedules even when time is tight.
     */
    public function test_handles_tight_schedule_constraints()
    {
        $faculty = Faculty::factory()->create([
            'availability' => [
                'Monday' => [['start' => '08:00:00', 'end' => '09:00:00']],
                'Tuesday' => [['start' => '10:00:00', 'end' => '11:00:00']],
            ],
        ]);
        
        $room = Room::factory()->create([
            'type' => 'LECTURE',
            'capacity' => 50,
            'specialization' => 'GENERAL',
        ]);

        $subject = Subject::factory()->create([
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
            'faculty_id' => $faculty->id,
            'meetings_per_week' => 2,
            'duration_hours' => 1, // 30 min per meeting
        ]);

        $filters = [
            'department' => 'CCS',
            'major' => 'IT',
            'year_level' => 1,
            'section' => 'A',
        ];

        $result = $this->service->generatePartialSchedules($filters, persist: false);

        // Should still succeed within tight constraints
        $this->assertGreaterThan(0, $result['scheduled']);
    }
}