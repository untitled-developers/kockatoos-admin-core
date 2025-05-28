<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits;

use UntitledDevelopers\KockatoosAdminCore\Models\Language;
use DB;
use Illuminate\Support\Str;

trait LanguageableCrud
{
    protected string $languageTablePrefix = '_languages';
    protected string $languageModelClass;


    public function getLanguageTable(): string
    {
        return Str::snake(class_basename($this->modelClass)) . $this->languageTablePrefix;
    }

    /**
     * @throws \Exception
     */
    public function updateLanguages(array $fields, array $data, $modelId, callable $callback = null) {
        $languages = Language::query()->get();
        $prefix = Str::snake(class_basename($this->modelClass));
        $notFoundLanguages = [];

        //Data cleanup
        foreach ($data as $key => $languageData) {
            foreach ($fields as $field) {
                if(!isset($languageData[$field]) || $languageData[$field] == '') {
                    $data[$key][$field] = null;
                }
            }
        }
        $languageTable = $this->getLanguageTable();

        DB::table($languageTable)
            ->where($prefix . '_id', $modelId)
            ->delete();
        foreach ($languages as $language) {
            // Find the language in the $data
            $foundLanguage = collect($data)->first(function ($item) use ($language) {
                return $item['language_id'] == $language->id;
            });

            if($foundLanguage == null) {
                $notFoundLanguages[] = $language;
                continue;
            }


            $languageModel = new $this->languageModelClass;
            $languageModel->language_id = $language->id;
            $languageModel->{$prefix . '_id'} = $modelId;
            foreach ($fields as $field) {
                $languageModel->{$field} = $this->getLanguageFieldData($field, $data, $foundLanguage);
                if ($callback != null) {
                    $languageModel->{$field} = $callback($field, $language, $foundLanguage);
                }
            }
            $languageModel->save();

        }
        if(count($notFoundLanguages) > 0) {
            if(count($notFoundLanguages) == $languages->count()) {
                throw new \Exception('No languages found in the request matching the database languages');
            }
        }
    }

    private function getLanguageFieldData($field, $data, $foundLanguage) {
        if($foundLanguage[$field] != null) {
            return $foundLanguage[$field];
        }
        // Get the first language that has the field
        $language = collect($data)->first(function ($item) use ($field) {
            return $item[$field] != null;
        });
        if($language == null) {
            return null;
        }
        return $language[$field];
    }
}
