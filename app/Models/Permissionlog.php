<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PermissionLog – audit trail for every permission-related action.
 *
 * Actions tracked:
 *   grant_registrar_finalize   – Admin granted Registrar finalization access
 *   revoke_registrar_finalize  – Admin revoked Registrar finalization access
 *   finalized_schedule         – Registrar/Admin finalized a schedule block
 *
 * Usage:
 *   PermissionLog::record(
 *       action: PermissionLog::ACTION_GRANT,
 *       performer: Auth::user(),
 *       target: $registrar,
 *       context: ['registrar_name' => $registrar->name],
 *       description: 'Admin granted access to Registrar Jane.'
 *   );
 */
class PermissionLog extends Model
{
    // ── Action constants ──────────────────────────────────────────────────────

    /** Admin granted Registrar finalization access globally. */
    public const ACTION_GRANT = 'grant_registrar_finalize';

    /** Admin revoked Registrar finalization access. */
    public const ACTION_REVOKE = 'revoke_registrar_finalize';

    /** A user (Admin or Registrar) finalized a schedule block. */
    public const ACTION_FINALIZED = 'finalized_schedule';

    // ── Action metadata ───────────────────────────────────────────────────────

    protected const ACTION_META = [
        self::ACTION_GRANT => [
            'label' => 'Access Granted',
            'icon'  => '✅',
        ],
        self::ACTION_REVOKE => [
            'label' => 'Access Revoked',
            'icon'  => '🚫',
        ],
        self::ACTION_FINALIZED => [
            'label' => 'Schedule Finalized',
            'icon'  => '🔒',
        ],
    ];

    // ── Model config ──────────────────────────────────────────────────────────

    protected $fillable = [
        'action',
        'performed_by',
        'target_user_id',
        'context',
        'description',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * The user who performed the action (Admin granting/revoking, or user finalizing).
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * The user the action was performed ON (e.g., the Registrar receiving permission).
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Human-readable action label, e.g. "Access Granted".
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_META[$this->action]['label'] ?? ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Emoji icon representing the action type.
     */
    public function getActionIconAttribute(): string
    {
        return self::ACTION_META[$this->action]['icon'] ?? '📋';
    }

    // ── Factory method ────────────────────────────────────────────────────────

    /**
     * Convenience factory to create a log entry without verbose array syntax.
     *
     * @param  string      $action       One of the ACTION_* constants.
     * @param  User|null   $performer    The user who triggered the action.
     * @param  User|null   $target       The user the action targeted (nullable).
     * @param  array       $context      Freeform key-value context stored as JSON.
     * @param  string|null $description  Human-readable description for quick reading.
     */
    public static function record(
        string  $action,
        ?User   $performer,
        ?User   $target      = null,
        array   $context     = [],
        ?string $description = null,
    ): self {
        return self::create([
            'action'        => $action,
            'performed_by'  => $performer?->id,
            'target_user_id'=> $target?->id,
            'context'       => $context,
            'description'   => $description,
        ]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Filter to only permission-grant / revoke entries (excludes finalization logs).
     */
    public function scopePermissionOnly($query)
    {
        return $query->whereIn('action', [self::ACTION_GRANT, self::ACTION_REVOKE]);
    }

    /**
     * Filter to only schedule-finalization entries.
     */
    public function scopeFinalizationOnly($query)
    {
        return $query->where('action', self::ACTION_FINALIZED);
    }
}