<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blob extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'url',
        'type',
        'size',
        'ext',
        'name',
        'directory',
        'sort_number',
    ];

    protected $casts = [
        'size'        => 'integer',
        'sort_number' => 'integer',
    ];

    public function getIsImageAttribute(): bool
    {
        return Str::startsWith($this->type, 'image/');
    }
}