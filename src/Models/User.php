<?php

namespace Rais\MomoSuite\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Rais\MomoSuite\Models\Traits\HasUuid;

class User extends Authenticatable
{
    use HasUuid;

    protected $table = 'momo_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the is_admin attribute.
     *
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
