<?php

namespace App\Services\Retrieve;

/**
 * Immutable report produced by CompatibilityChecker.
 *
 * The Livewire component renders this as the "Configuration Differences"
 * table in the wizard's compatibility step.  When isCompatible() is true
 * the wizard can skip that step entirely.
 */
final class CompatibilityReport
{
    public function __construct(
        /** Configuration field differences (start_time, end_time, active_days). */
        public readonly array $configDifferences   = [],

        /** Days in the archive that are inactive in the current config. */
        public readonly array $inactiveDays         = [],

        /**
         * Faculty IDs referenced by the archive that no longer exist or have
         * been deactivated/deleted in the system.
         */
        public readonly array $missingFacultyIds    = [],

        /**
         * Room IDs referenced by the archive that no longer exist.
         */
        public readonly array $missingRoomIds       = [],

        /**
         * Schedules (by archived schedule ID) that would fall outside the
         * current semester's configured hours.
         */
        public readonly array $outOfBoundsSchedules = [],
    ) {}

    /** True when there are zero differences — safe to proceed without confirmation. */
    public function isCompatible(): bool
    {
        return $this->configDifferences   === []
            && $this->inactiveDays         === []
            && $this->missingFacultyIds    === []
            && $this->missingRoomIds       === []
            && $this->outOfBoundsSchedules === [];
    }

    public function hasDifferences(): bool
    {
        return ! $this->isCompatible();
    }

    public function issueCount(): int
    {
        return count($this->configDifferences)
             + count($this->inactiveDays)
             + count($this->missingFacultyIds)
             + count($this->missingRoomIds)
             + count($this->outOfBoundsSchedules);
    }

    public function toArray(): array
    {
        return [
            'config_differences'    => $this->configDifferences,
            'inactive_days'         => $this->inactiveDays,
            'missing_faculty_ids'   => $this->missingFacultyIds,
            'missing_room_ids'      => $this->missingRoomIds,
            'out_of_bounds'         => $this->outOfBoundsSchedules,
            'is_compatible'         => $this->isCompatible(),
            'issue_count'           => $this->issueCount(),
        ];
    }
}