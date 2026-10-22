<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'branch_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'role' => 'nasabah',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $role = $role instanceof Role ? $role : Role::from($role);
            if ($this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Staf cabang hanya melihat data cabangnya; admin & komite melihat seluruh cabang. */
    public function seesAllBranches(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Komite], true) || $this->branch_id === null;
    }

    public function homeRoute(): string
    {
        return $this->isStaff() ? route('admin.dashboard') : route('customer.dashboard');
    }
}
