<?php


namespace UntitledDevelopers\KockatoosAdminCore\Facades;
use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Auth
 * @package App\Facades=
 */
class Auth extends \Illuminate\Support\Facades\Auth
{

    public static function user($shouldCreateNew = false): Model|Admin|Authenticatable|null
    {
        return parent::user();
    }

    public static function id(): int|null|string
    {
        return self::user()->id;
    }
}
