<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('rooms', 'room_type')) {
                $table->string('room_type')->nullable()->after('type');
            }

            if (! Schema::hasColumn('rooms', 'allowed_departments')) {
                $table->json('allowed_departments')->nullable()->after('room_type');
            }

            if (! Schema::hasColumn('rooms', 'department_owner')) {
                $table->string('department_owner', 30)->nullable()->after('allowed_departments');
            }

            if (! Schema::hasColumn('rooms', 'is_specialized')) {
                $table->boolean('is_specialized')->default(false)->after('department_owner');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'requires_lab')) {
                $table->boolean('requires_lab')->default(false)->after('subject_type');
            }

            if (! Schema::hasColumn('subjects', 'preferred_room_type')) {
                $table->string('preferred_room_type')->nullable()->after('requires_lab');
            }
        });

        DB::table('rooms')
            ->whereNull('room_type')
            ->update(['room_type' => DB::raw('type')]);

        DB::table('rooms')
            ->whereNull('department_owner')
            ->orderBy('id')
            ->chunkById(200, function ($rooms) {
                foreach ($rooms as $room) {
                    $owner = $this->inferDepartmentOwner((string) ($room->specialization ?? ''), (string) ($room->room_name ?? ''));
                    $isSpecialized = $this->roomLooksSpecialized($room);

                    DB::table('rooms')->where('id', $room->id)->update([
                        'department_owner' => $owner,
                        'is_specialized' => $isSpecialized,
                    ]);
                }
            });

        DB::table('subjects')
            ->where('requires_lab', false)
            ->orderBy('id')
            ->chunkById(200, function ($subjects) {
                foreach ($subjects as $subject) {
                    $requiresLab = $this->subjectLooksLabBased($subject);

                    DB::table('subjects')->where('id', $subject->id)->update([
                        'requires_lab' => $requiresLab,
                        'preferred_room_type' => $requiresLab ? 'LAB' : 'LECTURE',
                    ]);
                }
            });

        Schema::table('rooms', function (Blueprint $table) {
            if (! Schema::hasIndex('rooms', 'idx_rooms_smart_filtering')) {
                $table->index(['room_type', 'department_owner', 'is_specialized'], 'idx_rooms_smart_filtering');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasIndex('subjects', 'idx_subjects_room_requirements')) {
                $table->index(['requires_lab', 'preferred_room_type'], 'idx_subjects_room_requirements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasIndex('subjects', 'idx_subjects_room_requirements')) {
                $table->dropIndex('idx_subjects_room_requirements');
            }

            $columns = collect(['requires_lab', 'preferred_room_type'])
                ->filter(fn (string $column) => Schema::hasColumn('subjects', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (Schema::hasIndex('rooms', 'idx_rooms_smart_filtering')) {
                $table->dropIndex('idx_rooms_smart_filtering');
            }

            $columns = collect(['room_type', 'allowed_departments', 'department_owner', 'is_specialized'])
                ->filter(fn (string $column) => Schema::hasColumn('rooms', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function inferDepartmentOwner(string $specialization, string $roomName): ?string
    {
        $text = strtoupper(trim($specialization.' '.$roomName));

        return match (true) {
            $this->containsAny($text, ['CCS', 'IT', 'ACT', 'ICT', 'COMPUTER', 'WORKSHOP']) => 'CCS',
            $this->containsAny($text, ['CTE', 'ED', 'EDUCATION']) => 'CTE',
            $this->containsAny($text, ['SHTM', 'HM', 'TM', 'HRM', 'HOSPITALITY', 'KITCHEN']) => 'SHTM',
            $this->containsAny($text, ['COC', 'FB', 'LD', 'QD', 'FORENSIC', 'CRIM']) => 'COC',
            default => null,
        };
    }

    private function roomLooksSpecialized(object $room): bool
    {
        $text = strtoupper(trim(($room->room_name ?? '').' '.($room->type ?? '').' '.($room->specialization ?? '')));

        return $this->containsAny($text, [
            'LAB',
            'LABORATORY',
            'COMPUTER',
            'ICT',
            'WORKSHOP',
            'KITCHEN',
            'HOSPITALITY',
            'FORENSIC',
            'BALLISTICS',
        ]);
    }

    private function subjectLooksLabBased(object $subject): bool
    {
        $text = strtoupper(trim(implode(' ', [
            (string) ($subject->type ?? ''),
            (string) ($subject->subject_type ?? ''),
            (string) ($subject->subject_code ?? ''),
            (string) ($subject->description ?? ''),
            (string) ($subject->specialization ?? ''),
            (string) ($subject->major ?? ''),
        ])));

        return $this->containsAny($text, [
            'LAB',
            'LABORATORY',
            'PROGRAMMING',
            'NETWORKING',
            'DATABASE',
            'SYSTEMS',
            'PRACTICAL',
            'KITCHEN',
            'CULINARY',
            'WORKSHOP',
        ]);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
};
