<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable

{
    use Notifiable, HasApiTokens;

    protected $table = 'admins';

    protected $fillable = [
        'email', 'password', 'phone', 'username', 'name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];
    protected $appends = ['role_name', 'role_display_name'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getRoleNameAttribute()
    {
        return $this->role?->name;
    }

    public function getRoleDisplayNameAttribute()
    {
        return $this->role?->display_name;
    }

    /**
     * Check if this admin is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    /**
     * Check if this admin is a group admin.
     */
    public function isGroupAdmin(): bool
    {
        return $this->role?->name === 'group_admin';
    }

    public function hasPermission($name)
    {
        $this->loadPermissionsRelations();

        $_permissions = $this->roles_all()
            ->pluck('permissions')->flatten()
            ->pluck('key')->unique()->toArray();

        return in_array($name, $_permissions);
    }

    private function loadPermissionsRelations()
    {
        $this->loadRolesRelations();

        if ($this->role && !$this->role->relationLoaded('permissions')) {
            $this->role->load('permissions');
        }
    }



}
