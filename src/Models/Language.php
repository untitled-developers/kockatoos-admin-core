<?php

namespace UntitledDevelopers\KockatoosAdminCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends BaseModel
{
    use HasFactory;

    protected $fillable = ["code", "name"];
}
