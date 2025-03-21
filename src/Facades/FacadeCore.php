<?php

namespace UntitledDevelopers\KockatoosAdminCore\Facades;

use Illuminate\Support\Facades\Facade;
use UntitledDevelopers\KockatoosAdminCore\Core;

/**
 * @see \UntitledDevelopers\KockatoosAdminCore\Core
 */
class FacadeCore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Core::class;
    }
}
