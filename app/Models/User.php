<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_PLANNER = 'planner';
    const ROLE_GRADER = 'grader';
    const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'name', 'email', 'password', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean'];

    // Relationships
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    // Role management methods
    public function getRoles(): Collection
    {
        return $this->userRoles()->pluck('role');
    }

    public function hasRole(string $role): bool
    {
        return $this->userRoles()->where('role', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->userRoles()->whereIn('role', $roles)->exists();
    }

    public function assignRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            $this->userRoles()->create(['role' => $role]);
        }
    }

    public function removeRole(string $role): void
    {
        $this->userRoles()->where('role', $role)->delete();
    }

    public function syncRoles(array $roles): void
    {
        $this->userRoles()->delete();
        foreach ($roles as $role) {
            $this->assignRole($role);
        }
    }

    // Permission methods
    public function canEdit(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_PLANNER]);
    }

    public function canGrade(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN,  self::ROLE_GRADER]);
    }

    public function canManageUsers(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function canManageConfig(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_PLANNER]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }
     public function isViewer(): bool
    {
        return $this->hasRole(self::ROLE_VIEWER);
    }

    // Legacy compatibility - primary role
    public function getRoleAttribute(): ?string
    {
        $roles = $this->getRoles();
        
        // Priority order for primary role display
        if ($roles->contains(self::ROLE_ADMIN)) return self::ROLE_ADMIN;
        if ($roles->contains(self::ROLE_PLANNER)) return self::ROLE_PLANNER;
        if ($roles->contains(self::ROLE_GRADER)) return self::ROLE_GRADER;
        if ($roles->contains(self::ROLE_VIEWER)) return self::ROLE_VIEWER;
        
        return $roles->first();
    }

    public function getRoleDisplayAttribute(): string
    {
        $roles = $this->getRoles();
        $roleLabels = self::getRoleLabels();
        
        return $roles->map(function($role) use ($roleLabels) {
            return $roleLabels[$role] ?? ucfirst($role);
        })->join(', ');
    }

    // Static methods
    public static function getRoleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PLANNER => 'Planner',
            self::ROLE_GRADER => 'Grader',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }

    public static function getAllRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_PLANNER,
            self::ROLE_GRADER,
            self::ROLE_VIEWER,
        ];
    }

    public function activityLogs()
{
    return $this->hasMany(UserActivityLog::class);
}

/**
 * Get recent activity logs for the user
 */
public function recentActivityLogs($limit = 10)
{
    return $this->activityLogs()
        ->orderBy('created_at', 'desc')
        ->limit($limit);
}

/**
 * Check if user has made any changes recently
 */
public function hasRecentActivity($days = 7)
{
    return $this->activityLogs()
        ->where('created_at', '>=', now()->subDays($days))
        ->exists();
}
}