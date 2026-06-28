<?php

namespace App\Livewire\Reports;

use App\Models\Setting;
use App\Models\Subject;
use App\Services\ScheduleReportService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PreliminaryScheduleModal extends Component
{
    // ── UI state ──────────────────────────────────────────────────────────────
    public bool $showModal    = false;
    public bool $isGenerating = false;
    public bool $hasGenerated = false;

    // ── Filters ───────────────────────────────────────────────────────────────
    public string $filterDept      = '';
    public string $filterMajor     = '';
    public string $filterYearLevel = '';
    public string $filterSection   = '';
    /** @var string[] */
    public array  $filterTypes     = ['Major', 'Minor'];

    // Schedule status — "Preliminary" is the only one wired to real data
    // today. Final / Published are kept as real (disabled) options in the
    // dropdown so the report can support them later without revisiting the
    // UI again.
    public const STATUS_OPTIONS = [
        'Preliminary' => 'Preliminary',
        'Final'       => 'Final',
        'Published'   => 'Published',
    ];

    public string $filterStatus = 'Preliminary';

    // Free-text search — filters the rows already loaded into $reportData,
    // so typing never triggers another database query.
    public string $search = '';

    // ── Print scope ───────────────────────────────────────────────────────────
    // 'all'     → print every group that matches the current filters (default)
    // 'current' → print only the group currently highlighted / first group
    // The user can toggle this before clicking Print.
    public string $printScope = 'all';

    // ── Report output ─────────────────────────────────────────────────────────
    public array $reportData = [];

    // ── Cascade filter reset + live regeneration ────────────────────────────

    public function updatedFilterDept(): void
    {
        $this->filterMajor   = '';
        $this->filterSection = '';
        $this->refreshIfOpen();
    }

    public function updatedFilterMajor(): void
    {
        $this->filterSection = '';
        $this->refreshIfOpen();
    }

    public function updatedFilterYearLevel(): void
    {
        $this->filterSection = '';
        $this->refreshIfOpen();
    }

    public function updatedFilterSection(): void
    {
        $this->refreshIfOpen();
    }

    public function updatedFilterTypes(): void
    {
        // Guard against the user unchecking both boxes.
        if (empty($this->filterTypes)) {
            $this->filterTypes = ['Major', 'Minor'];
        }

        $this->refreshIfOpen();
    }

    public function updatedFilterStatus(): void
    {
        $this->refreshIfOpen();
    }

    private function refreshIfOpen(): void
    {
        if ($this->showModal) {
            $this->generateReport();
        }
    }

    // ── Event listener: triggered by the button in manage-subjects blade ──────

    #[On('open-preliminary-schedule')]
    public function openModal(): void
    {
        $user     = auth()->user();
        $userRole = strtolower($user->role ?? '');

        // Seed department based on role — power users start with "All"
        if (! in_array($userRole, ['admin', 'registrar', 'associate_dean'], true)) {
            $this->filterDept = strtoupper($user->department ?? '');
        } else {
            $this->filterDept = '';
        }

        $this->filterMajor     = '';
        $this->filterYearLevel = '';
        $this->filterSection   = '';
        $this->filterTypes     = ['Major', 'Minor'];
        $this->filterStatus    = 'Preliminary';
        $this->search          = '';
        $this->printScope      = 'all';
        $this->reportData      = [];
        $this->hasGenerated    = false;
        $this->showModal       = true;

        // Auto-generate with default filters
        $this->generateReport();
    }

    public function closeModal(): void
    {
        $this->showModal    = false;
        $this->hasGenerated = false;
        $this->reportData   = [];
        $this->search       = '';
        $this->printScope   = 'all';
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    // ── Report generation ─────────────────────────────────────────────────────

    public function generateReport(): void
    {
        // Ensure at least one subject type is selected
        if (empty($this->filterTypes)) {
            $this->filterTypes = ['Major', 'Minor'];
        }

        $this->isGenerating = true;

        $service = app(ScheduleReportService::class);

        $this->reportData = $service->buildPreliminaryReport([
            'department'    => $this->filterDept,
            'major'         => $this->filterMajor,
            'year_level'    => $this->filterYearLevel,
            'section'       => $this->filterSection,
            'subject_types' => $this->filterTypes,
            // 'status' => $this->filterStatus, // wire in once Final/Published
            //                                      reports exist in the service
        ]);

        $this->hasGenerated = true;
        $this->isGenerating = false;
    }

    // ── Computed reactive data for dropdowns ──────────────────────────────────

    #[Computed]
    public function availableMajors(): Collection
    {
        return app(ScheduleReportService::class)->getAvailableMajors($this->filterDept);
    }

    #[Computed]
    public function availableSections(): Collection
    {
        return app(ScheduleReportService::class)->getAvailableSections(
            $this->filterDept,
            $this->filterMajor,
            $this->filterYearLevel
        );
    }

    #[Computed]
    public function activePeriod(): array
    {
        return Setting::getAcademicPeriod();
    }

    #[Computed]
    public function isPowerUser(): bool
    {
        return in_array(
            strtolower(auth()->user()->role ?? ''),
            ['admin', 'registrar', 'associate_dean'],
            true
        );
    }

    // ── Search applied to the already-generated rows ───────────────────────────
    // This is intentionally NOT a database query — it filters the rows that
    // `generateReport()` already fetched, so typing in the search box never
    // re-hits the database.

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function filteredRows(): array
    {
        $rows = $this->reportData['rows'] ?? [];

        if (empty($rows)) {
            return [];
        }

        $term = mb_strtolower(trim($this->search));

        if ($term === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($term) {
            $haystack = mb_strtolower(implode(' ', [
                $row['subject_code'] ?? '',
                $row['edp_code']     ?? '',
                $row['description']  ?? '',
                $row['faculty']      ?? '',
                $row['section']      ?? '',
                $row['major']        ?? '',
                $row['department']   ?? '',
            ]));

            return str_contains($haystack, $term);
        }));
    }

    #[Computed]
    public function searchResultCount(): int
    {
        return count($this->filteredRows());
    }

    // ── Grouped rows for display ──────────────────────────────────────────────

    #[Computed]
    public function groupedRows(): array
    {
        $rows = $this->filteredRows();

        if (empty($rows)) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {
            $key = $row['department'] . '|' . $row['major'] . '|' . $row['year_level'] . '|' . $row['section'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'department'  => $row['department'],
                    'major'       => $row['major'],
                    'year_level'  => $row['year_level'],
                    'year_label'  => ScheduleReportService::yearLabel($row['year_level']),
                    'section'     => $row['section'],
                    'rows'        => [],
                    'total_units' => 0,
                ];
            }

            $groups[$key]['rows'][]       = $row;
            $groups[$key]['total_units'] += (int) ($row['units'] ?? 0);
        }

        return array_values($groups);
    }

    // ── Groups filtered by print scope ────────────────────────────────────────
    // 'all'     → every group (respects active filters + search)
    // 'current' → only the first group (what the user sees at the top of preview)

    #[Computed]
    public function printableGroups(): array
    {
        $groups = $this->groupedRows();

        if ($this->printScope === 'current') {
            return array_slice($groups, 0, 1);
        }

        return $groups;
    }

    // ── Report-level summary (used in the report header and print cover) ──────
    // Derived from groupedRows so it always stays in sync with the search filter
    // without any extra database work.

    #[Computed]
    public function reportSummary(): array
    {
        $groups = $this->groupedRows();

        return [
            'total_groups' => count($groups),
            'total_units'  => (int) array_sum(array_column($groups, 'total_units')),
        ];
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.reports.preliminary-schedule-modal', [
            'activePeriod'      => $this->activePeriod,
            'isPowerUser'       => $this->isPowerUser,
            'availableMajors'   => $this->availableMajors,
            'availableSections' => $this->availableSections,
            'groupedRows'       => $this->groupedRows,
            'printableGroups'   => $this->printableGroups,
            'searchResultCount' => $this->searchResultCount,
            'statusOptions'     => self::STATUS_OPTIONS,
            'reportSummary'     => $this->reportSummary,
        ]);
    }
}