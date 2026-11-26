<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'admin_role_id', 'team_manager_id', 'is_active', 'must_change_password', 'two_factor_secret', 'two_factor_enabled', 'two_factor_recovery_codes', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<AdminRole, $this> */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teamManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_manager_id');
    }

    /** @return HasMany<User, $this> */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'team_manager_id');
    }

    /** @return list<int> */
    public function operationalTeamUserIds(): array
    {
        if ($this->adminRole?->slug !== 'sales-manager') {
            return [$this->id];
        }

        return $this->teamMembers()->where('is_active', true)->pluck('id')->push($this->id)->map(fn ($id): int => (int) $id)->all();
    }

    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->is_active || ! $this->adminRole) {
            return false;
        }

        $permissions = $this->adminRole->permissions ?? [];

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
