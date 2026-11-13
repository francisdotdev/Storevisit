<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'branch_code',
        'status',
        'approved_by',
        'approved_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Full name accessor.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}")
        );
    }

    /**
     * Name accessor.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}")
        );
    }

    /**
     * Helper methods for role and status checks.
     */
    public function isAdmin(): bool
    {
        return $this->role?->name === \App\Services\AuthorizationService::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role?->name === \App\Services\AuthorizationService::ROLE_MANAGER;
    }

    public function isAssistantManager(): bool
    {
        return in_array($this->role?->name, [
            \App\Services\AuthorizationService::ROLE_ASST_TREASURY_MANAGER,
            \App\Services\AuthorizationService::ROLE_ASST_CASHIER_MANAGER,
        ], true);
    }

    public function isSupervisor(): bool
    {
        return in_array($this->role?->name, [
            \App\Services\AuthorizationService::ROLE_AREA_TREASURY_SUPERVISOR,
            \App\Services\AuthorizationService::ROLE_AREA_CASHIER_SUPERVISOR,
        ], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param string|array<string> $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roleName = $this->role?->name;
        if (!$roleName) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($roleName, $roles, true);
        }

        return $roleName === $roles;
    }

    /**
     * Check if user possesses a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $roleName = $this->role?->name;
        if (!$roleName) {
            return false;
        }

        return \App\Services\AuthorizationService::roleHasPermission($roleName, $permission);
    }

    /**
     * Get all permissions assigned to this user's role.
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        $roleName = $this->role?->name;
        if (!$roleName) {
            return [];
        }

        return \App\Services\AuthorizationService::getPermissionsForRole($roleName);
    }

    /**
     * Get the audit department type scope for this user ('treasury', 'cashier', or null).
     */
    public function getAuditType(): ?string
    {
        $roleName = $this->role?->name;
        if (!$roleName) {
            return null;
        }

        return \App\Services\AuthorizationService::getAuditTypeForRole($roleName);
    }

    /**
     * Check if user is authorized to access a specific audit department type.
     */
    public function canAccessAuditType(string $auditType): bool
    {
        // Admin and Manager can access both departments
        if ($this->isAdmin() || $this->isManager()) {
            return true;
        }

        $userType = $this->getAuditType();
        return $userType === $auditType;
    }

    /**
     * Check if user is authorized to access records for a specific store.
     * Enforces BRD rule: Area Supervisors can only access their authorized assigned stores.
     *
     * @param int|\App\Models\Store $store
     * @param string|null $auditType
     */
    public function canAccessStore(int|Store $store, ?string $auditType = null): bool
    {
        $storeId = $store instanceof Store ? $store->id : $store;

        // Admin and Manager have global store access
        if ($this->isAdmin() || $this->isManager()) {
            return true;
        }

        // Assistant Managers have access to all stores within their department
        if ($this->isAssistantManager()) {
            if ($auditType !== null) {
                return $this->canAccessAuditType($auditType);
            }
            return true;
        }

        // Area Supervisors: Strictly restricted to their assigned stores in supervisor_store_assignments
        if ($this->isSupervisor()) {
            $userAuditType = $this->getAuditType();

            // If an explicit audit type is requested, verify department match
            if ($auditType !== null && $auditType !== $userAuditType) {
                return false;
            }

            return $this->storeAssignments()
                ->where('store_id', $storeId)
                ->where('audit_type', $userAuditType)
                ->exists();
        }

        return false;
    }

    /**
     * Get the role associated with the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the admin who approved this user.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get users approved by this user.
     */
    public function approvedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    /**
     * Store assignments for supervisor.
     */
    public function storeAssignments(): HasMany
    {
        return $this->hasMany(SupervisorStoreAssignment::class, 'supervisor_id');
    }

    /**
     * Stores assigned to this supervisor.
     */
    public function assignedStores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'supervisor_store_assignments', 'supervisor_id', 'store_id')
            ->withPivot('audit_type', 'assigned_by')
            ->withTimestamps();
    }

    /**
     * Visit schedules assigned to this supervisor.
     */
    public function visitSchedules(): HasMany
    {
        return $this->hasMany(StoreVisitSchedule::class, 'supervisor_id');
    }

    /**
     * Visit schedules created by this user (Assistant Manager).
     */
    public function createdSchedules(): HasMany
    {
        return $this->hasMany(StoreVisitSchedule::class, 'scheduled_by');
    }

    /**
     * Observations logged by this supervisor.
     */
    public function observations(): HasMany
    {
        return $this->hasMany(AuditObservation::class, 'supervisor_id');
    }

    /**
     * Audit ratings received by this supervisor.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(AuditVisitRating::class, 'supervisor_id');
    }
}
