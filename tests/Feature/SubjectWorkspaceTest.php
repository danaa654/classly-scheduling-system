<?php

use App\Models\Setting;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function subjectPayload(array $overrides = []): array
{
    return array_merge([
        'edp_code' => 'IT-2611001',
        'subject_code' => 'IT 101',
        'section' => 'A',
        'description' => 'Introduction to Computing',
        'major' => 'IT',
        'year_level' => 1,
        'department' => 'CCS',
        'units' => 3,
        'duration_hours' => 3,
        'type' => 'Major',
        'subject_type' => 'Major',
        'specialization' => 'IT',
        'meetings_per_week' => 1,
        'semester' => '1st',
        'school_year' => '2026-2027',
        'academic_year' => '2026-2027',
    ], $overrides);
}

test('edp generation includes semester and increments inside the active workspace', function () {
    Setting::setValue('school_year', '2026-2027');
    Setting::setValue('semester', '1st');

    Subject::create(subjectPayload());

    expect(Subject::generateEdpCode('IT', 1, '2026-2027', '1st'))->toBe('IT-2611002')
        ->and(Subject::generateEdpCode('IT', 1, '2026-2027', '2nd'))->toBe('IT-2621001')
        ->and(Subject::generateEdpCode('IT', 2, '2026-2027', '2nd'))->toBe('IT-2622001');
});

test('same edp code can exist in different semester workspaces only', function () {
    Subject::create(subjectPayload([
        'edp_code' => 'IT-261001',
        'semester' => '1st',
    ]));

    Subject::create(subjectPayload([
        'edp_code' => 'IT-261001',
        'semester' => '2nd',
        'subject_code' => 'IT 102',
    ]));

    expect(Subject::where('edp_code', 'IT-261001')->count())->toBe(2)
        ->and(Subject::edpExistsInWorkspace('IT-261001', '2026-2027', '1st'))->toBeTrue()
        ->and(Subject::edpExistsInWorkspace('IT-261001', '2026-2027', 'Summer'))->toBeFalse();

    expect(fn () => Subject::create(subjectPayload([
        'edp_code' => 'IT-261001',
        'semester' => '2nd',
        'subject_code' => 'IT 103',
    ])))->toThrow(QueryException::class);
});

test('active term scope excludes archived and other semester subjects', function () {
    $active = Subject::create(subjectPayload([
        'edp_code' => 'IT-2621001',
        'semester' => '2nd',
    ]));

    Subject::create(subjectPayload([
        'edp_code' => 'IT-2611001',
        'semester' => '1st',
    ]));

    Subject::create(subjectPayload([
        'edp_code' => 'IT-2621002',
        'semester' => '2nd',
        'is_archived' => true,
        'archived_at' => now(),
    ]));

    expect(Subject::activeTerm('2nd', '2026-2027')->pluck('id')->all())->toBe([$active->id]);
});
