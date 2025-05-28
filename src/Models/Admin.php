<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable

{
    use HasApiTokens;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'is_locked',

    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }


}
