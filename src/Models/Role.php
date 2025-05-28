<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    protected $fillable = [
        'name',
        'display_name'
    ];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class);

    }

}
