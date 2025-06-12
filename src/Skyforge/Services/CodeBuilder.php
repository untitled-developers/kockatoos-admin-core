<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Services;

class CodeBuilder
{


    public function associateArrayToCodeString(array $array): string
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = "   '$key' => '$value',";
        }
        return implode("\n", $result);
    }
}
