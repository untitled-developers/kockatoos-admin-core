<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ValidateModel
{
    use ValidatesRequests;
    protected bool $shouldValidate = false;
    protected bool $validateLanguages = false;

    protected function validateModel($class, $data, $isNew): array
    {
        $data = json_decode(json_encode($data), true);
        // Get validation statically

        if($this->validateLanguages && isset($data['languages'])) {
            $this->validateLanguages($data['languages'], $class, $isNew);
        }

        $rules = $class::getValidationRules($isNew);
        $attributeNames = $class::getValidationAttributesNames();

        $validator = $this->getValidationFactory()->make($data, $rules, [], []);
        $validator->setAttributeNames($attributeNames);
        return $validator->validate();
    }

    protected function validateLanguages($data, $class, $isNew) {
        $rules = $class::getLanguageValidationRules($isNew);


        //get language table keys
        $languageModelClass = $this->languageModelClass;
        $languageModel = new $languageModelClass;
        $languageTable = $languageModel->getTable();
        $languageFields = DB::select("SHOW COLUMNS FROM $languageTable");
        $languageFields = array_map(function($field) {
            return $field->Field;
        }, $languageFields);

        $keys = $languageFields;
        foreach ($data as $languageData) {
            $keys = array_unique(array_merge($keys, array_keys($languageData)), SORT_REGULAR) ;
        }

        foreach ($keys as $field) {

            $languageIndex = 0;
            foreach ($data as $key => $languageData) {
                if (!isset($languageData[$field]) || $languageData[$field] == '') {
                    $languageField = null;
                } else {
                    $languageField = $languageData[$field];
                }
                $validator = $this->getValidationFactory()->make([$field => $languageField], [$field => $rules[$field] ?? ''], [], []);

                try {
                    $validator->validate();
                    continue;
                } catch (ValidationException $e) {
                    if($languageIndex == count($data) - 1) {
                        throw $e;
                    }
                }
                $languageIndex++;
            }

        }


    }
}
