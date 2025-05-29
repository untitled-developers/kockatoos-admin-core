<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;


use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\CrudController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchableField;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchTypes;
use UntitledDevelopers\KockatoosAdminCore\Models\BaseModel;
use UntitledDevelopers\KockatoosAdminCore\Models\Blob;

class BlobsController extends CrudController
{
    protected string $table = 'blobs';
    protected string $modelClass = Blob::class;
    protected string $filesDirectory = 'blobs';
    protected array $searchFields;
    protected bool $safeDelete = false;
    protected array $selectColumns = [
        'blobs.id',
        'blobs.name',
        'blobs.size',
        'blobs.type',
        'blobs.created_at',
        'blobs.updated_at'
    ];

    #[Pure]
    public function __construct()
    {
        $this->searchFields = [
            SearchableField::create('blobs.id', SearchTypes::$EXACT),
            SearchableField::create('blobs.name', SearchTypes::$CONTAINS),
            SearchableField::create('blobs.size', SearchTypes::$EXACT),
            SearchableField::create('blobs.type', SearchTypes::$EXACT)
        ];
    }

    protected function saveModel(Request $request, BaseModel $model, bool $isNew): BaseModel
    {
        $data = $this->initSaveModel($request, $model);
        return $model;
    }


}
