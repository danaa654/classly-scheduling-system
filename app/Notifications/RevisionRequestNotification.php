<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * RevisionRequestNotification
 *
 * Sent to the original requester (Dean / OIC / Associate Dean) when their
 * faculty revision request is approved or rejected by an Admin / Registrar.
 *
 * Stored in the database via the existing `notifications` table so the
 * NotificationCenter Livewire component picks it up automatically.
 *
 * WHY A NEW FILE:
 *   GeneralNotification is a generic, one-size-fits-all notification.
 *   We need a dedicated class so we can carry structured revision data
 *   (subject code, faculty names, decision) in a typed, queryable way and
 *   build rich display strings without polluting GeneralNotification.
 */
class RevisionRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $data
    ) {}

    /** Only the database channel is used – mirrors GeneralNotification. */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Shape stored in `notifications.data` (JSON).
     *
     * Keys intentionally match what the notification-center blade already
     * renders for GeneralNotification so no blade changes are required there.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title'            => $this->data['title']            ?? 'Faculty Revision Update',
            'message'          => $this->data['message']          ?? '',
            'type'             => $this->data['type']             ?? 'info',   // success | warning | error | info
            'url'              => $this->data['url']              ?? null,
            'sender_name'      => $this->data['sender_name']      ?? null,

            // Revision-specific structured fields for richer display
            'subject_code'     => $this->data['subject_code']     ?? null,
            'subject_name'     => $this->data['subject_name']     ?? null,
            'current_faculty'  => $this->data['current_faculty']  ?? null,
            'requested_faculty'=> $this->data['requested_faculty'] ?? null,
            'decision'         => $this->data['decision']         ?? null,  // 'approved' | 'rejected'
            'review_note'      => $this->data['review_note']      ?? null,
            'requester_name'   => $this->data['requester_name']   ?? null,
            'requester_role'   => $this->data['requester_role']   ?? null,
            'reviewed_by'      => $this->data['reviewed_by']      ?? null,
            'reviewed_at'      => $this->data['reviewed_at']      ?? now()->toDateTimeString(),
        ];
    }

    // ── Convenience factory methods ───────────────────────────────────────────

    /**
     * Build the notification payload for an APPROVED revision.
     *
     * @param  array{
     *     subject_code: string,
     *     subject_name: string,
     *     current_faculty: string,
     *     requested_faculty: string,
     *     requester_name: string,
     *     requester_role: string,
     *     reviewer_name: string,
     *     url: string,
     * } $context
     */
    public static function approved(array $context): self
    {
        $subjectCode      = $context['subject_code']      ?? 'Subject';
        $currentFaculty   = $context['current_faculty']   ?? 'Previous Faculty';
        $requestedFaculty = $context['requested_faculty'] ?? 'New Faculty';
        $reviewerName     = $context['reviewer_name']     ?? 'Admin';
        $roleLabel        = self::formatRole($context['requester_role'] ?? '');

        $message = "Your request to change {$subjectCode} from {$currentFaculty} to {$requestedFaculty} has been approved by {$reviewerName}.";

        return new self([
            'title'             => 'Faculty Revision Approved',
            'message'           => $message,
            'type'              => 'success',
            'url'               => $context['url'] ?? null,
            'sender_name'       => $reviewerName,
            'subject_code'      => $subjectCode,
            'subject_name'      => $context['subject_name'] ?? null,
            'current_faculty'   => $currentFaculty,
            'requested_faculty' => $requestedFaculty,
            'decision'          => 'approved',
            'review_note'       => $context['review_note'] ?? null,
            'requester_name'    => $context['requester_name'] ?? null,
            'requester_role'    => $roleLabel,
            'reviewed_by'       => $reviewerName,
            'reviewed_at'       => now()->toDateTimeString(),
        ]);
    }

    /**
     * Build the notification payload for a REJECTED revision.
     *
     * @param  array{
     *     subject_code: string,
     *     subject_name: string,
     *     current_faculty: string,
     *     requested_faculty: string,
     *     requester_name: string,
     *     requester_role: string,
     *     reviewer_name: string,
     *     review_note: ?string,
     *     url: string,
     * } $context
     */
    public static function rejected(array $context): self
    {
        $subjectCode      = $context['subject_code']      ?? 'Subject';
        $requestedFaculty = $context['requested_faculty'] ?? 'Requested Faculty';
        $reviewerName     = $context['reviewer_name']     ?? 'Admin';
        $roleLabel        = self::formatRole($context['requester_role'] ?? '');
        $note             = $context['review_note'] ?? null;

        $message = "Your request to assign {$requestedFaculty} to {$subjectCode} was rejected by {$reviewerName}.";
        if ($note) {
            $message .= " Reason: {$note}";
        }

        return new self([
            'title'             => 'Faculty Revision Rejected',
            'message'           => $message,
            'type'              => 'warning',
            'url'               => $context['url'] ?? null,
            'sender_name'       => $reviewerName,
            'subject_code'      => $subjectCode,
            'subject_name'      => $context['subject_name'] ?? null,
            'current_faculty'   => $context['current_faculty'] ?? null,
            'requested_faculty' => $requestedFaculty,
            'decision'          => 'rejected',
            'review_note'       => $note,
            'requester_name'    => $context['requester_name'] ?? null,
            'requester_role'    => $roleLabel,
            'reviewed_by'       => $reviewerName,
            'reviewed_at'       => now()->toDateTimeString(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function formatRole(string $role): string
    {
        return match ($role) {
            'dean'           => 'Dean',
            'oic'            => 'OIC',
            'associate_dean' => 'Associate Dean',
            'admin'          => 'Admin',
            'registrar'      => 'Registrar',
            default          => ucfirst(str_replace('_', ' ', $role)),
        };
    }
}