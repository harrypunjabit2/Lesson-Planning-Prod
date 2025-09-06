<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable; // Removed HasApiTokens

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // ... rest of your role methods remain the same
    const ROLE_ADMIN = 'admin';
    const ROLE_PLANNER = 'planner';
    const ROLE_VIEWER = 'viewer';

    public static function getRoles()
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PLANNER => 'Planner',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPlanner()
    {
        return $this->role === self::ROLE_PLANNER;
    }

    public function isViewer()
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function canEdit()
    {
        return $this->isAdmin() || $this->isPlanner();
    }

    public function canManageUsers()
    {
        return $this->isAdmin();
    }

    public function getRoleDisplayAttribute()
    {
        $roles = self::getRoles();
        return $roles[$this->role] ?? ucfirst($this->role);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }
}