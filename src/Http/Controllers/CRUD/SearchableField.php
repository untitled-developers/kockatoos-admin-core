<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD;


use JetBrains\PhpStorm\Pure;

class SearchableField
{
    public string $fieldName;
    public string $searchType;

    /**
     * SearchableField constructor.
     * @param string $fieldName
     * @param string $searchType
     */
    private function __construct(string $fieldName, string $searchType)
    {
        $this->fieldName = $fieldName;
        $this->searchType = $searchType;
    }

    #[Pure]
    public static function create(string $fieldName, string $searchType): SearchableField
    {
        return new self($fieldName, $searchType);
    }
}
