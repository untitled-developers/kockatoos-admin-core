<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Services;

use Illuminate\Support\Facades\DB;


class SkyForgeService
{
    function getAllTableNames(): array
    {
        $tables = DB::connection()->getSchemaBuilder()->getTables(config('database.connections.mysql.database'));

        return array_map(function ($table) {
            return $table['name'];
        }, $tables);
    }

}
